<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use ReflectionClass;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\JavaScript\Encoder;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
use s9e\TextFormatter\Configurator\JavaScript\Minifiers\Noop;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
use s9e\TextFormatter\Configurator\RendererGenerators\XSLT;
class JavaScript
{
	protected $callbacks;
	protected $config;
	protected $configurator;
	public $encoder;
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
	protected $hints;
	protected $minifier;
	protected $xsl;
	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
		$this->encoder      = new Encoder;
	}
	public function getMinifier()
	{
		if (!isset($this->minifier))
			$this->minifier = new Noop;
		return $this->minifier;
	}
	public function getParser(array $config = \null)
	{
		$this->config = (isset($config)) ? $config : $this->configurator->asConfig();
		ConfigHelper::filterVariants($this->config, 'JS');
		$src = $this->getSource();
		$this->injectConfig($src);
		if (!empty($this->exportMethods))
		{
			$methods = [];
			foreach ($this->exportMethods as $method)
				$methods[] = "'" . $method . "':" . $method;
			$src .= "window['s9e'] = { 'TextFormatter': {" . \implode(',', $methods) . "} }\n";
		}
		$src = $this->getMinifier()->get($src);
		$src = '(function(){' . $src . '})()';
		return $src;
	}
	public function setMinifier($minifier)
	{
		if (\is_string($minifier))
		{
			$className = __NAMESPACE__ . '\\JavaScript\\Minifiers\\' . $minifier;
			$args = \array_slice(\func_get_args(), 1);
			if (!empty($args))
			{
				$reflection = new ReflectionClass($className);
				$minifier   = $reflection->newInstanceArgs($args);
			}
			else
				$minifier = new $className;
		}
		$this->minifier = $minifier;
		return $minifier;
	}
	protected function getHints()
	{
		$this->hints = [];
		$this->setRenderingHints();
		$this->setRulesHints();
		$this->setTagsHints();
		\ksort($this->hints);
		$js = "/** @const */ var HINT={};\n";
		foreach ($this->hints as $hintName => $hintValue)
			$js .= '/** @const */ HINT.' . $hintName . '=' . $this->encode($hintValue) . ";\n";
		return $js;
	}
	protected function getPluginsConfig()
	{
		$plugins = new Dictionary;
		foreach ($this->config['plugins'] as $pluginName => $pluginConfig)
		{
			if (!isset($pluginConfig['parser']))
				continue;
			unset($pluginConfig['className']);
			if (isset($pluginConfig['quickMatch']))
			{
				$valid = [
					'[[:ascii:]]',
					'[\\xC0-\\xDF][\\x80-\\xBF]',
					'[\\xE0-\\xEF][\\x80-\\xBF]{2}',
					'[\\xF0-\\xF7][\\x80-\\xBF]{3}'
				];
				$regexp = '#(?>' . \implode('|', $valid) . ')+#';
				if (\preg_match($regexp, $pluginConfig['quickMatch'], $m))
					$pluginConfig['quickMatch'] = $m[0];
				else
					unset($pluginConfig['quickMatch']);
			}
			$globalKeys = [
				'parser'      => 1,
				'quickMatch'  => 1,
				'regexp'      => 1,
				'regexpLimit' => 1
			];
			$globalConfig = \array_intersect_key($pluginConfig, $globalKeys);
			$localConfig  = \array_diff_key($pluginConfig, $globalKeys);
			if (isset($globalConfig['regexp']) && !($globalConfig['regexp'] instanceof Code))
				$globalConfig['regexp'] = RegexpConvertor::toJS($globalConfig['regexp'], \true);
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
	protected function getRegisteredVarsConfig()
	{
		$registeredVars = $this->config['registeredVars'];
		unset($registeredVars['cacheDir']);
		return $this->encode(new Dictionary($registeredVars));
	}
	protected function getRootContext()
	{
		return $this->encode($this->config['rootContext']);
	}
	protected function getSource()
	{
		$files = [
			'Parser/utils.js',
			'Parser/BuiltInFilters.js',
			'Parser/' . (\in_array('getLogger', $this->exportMethods) ? '' : 'Null') . 'Logger.js',
			'Parser/Tag.js',
			'Parser.js'
		];
		if (\in_array('preview', $this->exportMethods, \true))
			$files[] = 'render.js';
		$this->xsl = (new XSLT)->getXSL($this->configurator->rendering);
		$src = $this->getHints();
		foreach ($files as $filename)
		{
			if ($filename === 'render.js')
				$src .= '/** @const */ var xsl=' . \json_encode($this->xsl) . ";\n";
			$filepath = __DIR__ . '/../' . $filename;
			$src .= \file_get_contents($filepath) . "\n";
		}
		return $src;
	}
	protected function getTagsConfig()
	{
		$this->replaceCallbacks();
		$tags = new Dictionary;
		foreach ($this->config['tags'] as $tagName => $tagConfig)
		{
			if (isset($tagConfig['attributes']))
				$tagConfig['attributes'] = new Dictionary($tagConfig['attributes']);
			$tags[$tagName] = $tagConfig;
		}
		return $this->encode($tags);
	}
	public function encode($value)
	{
		return $this->encoder->encode($value);
	}
	protected function injectConfig(&$src)
	{
		$this->callbacks = [];
		$config = [
			'plugins'        => $this->getPluginsConfig(),
			'registeredVars' => $this->getRegisteredVarsConfig(),
			'rootContext'    => $this->getRootContext(),
			'tagsConfig'     => $this->getTagsConfig()
		];
		$src = \preg_replace_callback(
			'/(\\nvar (' . \implode('|', \array_keys($config)) . '))(;)/',
			function ($m) use ($config)
			{
				return $m[1] . '=' . $config[$m[2]] . $m[3];
			},
			$src
		);
		$src .= "\n" . \implode("\n", $this->callbacks) . "\n";
	}
	protected function replaceCallbacks()
	{
		foreach ($this->config['tags'] as $tagName => $tagConfig)
			$this->config['tags'][$tagName] = $this->replaceCallbacksInTagConfig($tagConfig);
	}
	protected function replaceCallbacksInAttributeConfig(array $config)
	{
		if (isset($config['filterChain']))
			foreach ($config['filterChain'] as $i => $filter)
				$config['filterChain'][$i] = $this->convertCallback('attributeFilter', $filter);
		if (isset($config['generator']))
			$config['generator'] = $this->convertCallback('attributeGenerator', $config['generator']);
		return $config;
	}
	protected function replaceCallbacksInTagConfig(array $config)
	{
		if (isset($config['filterChain']))
			foreach ($config['filterChain'] as $i => $filter)
				$config['filterChain'][$i] = $this->convertCallback('tagFilter', $filter);
		if (isset($config['attributes']))
			foreach ($config['attributes'] as $attrName => $attrConfig)
				$config['attributes'][$attrName] = $this->replaceCallbacksInAttributeConfig($attrConfig);
		return $config;
	}
	protected function buildCallbackArguments(array $params, array $localVars)
	{
		unset($params['parser']);
		$localVars += ['logger' => 1, 'openTags' => 1, 'registeredVars' => 1];
		$args = [];
		foreach ($params as $k => $v)
			if (isset($v))
				$args[] = $this->encode($v);
			elseif (isset($localVars[$k]))
				$args[] = $k;
			else
				$args[] = 'registeredVars[' . \json_encode($k) . ']';
		return \implode(',', $args);
	}
	protected function convertCallback($type, array $config)
	{
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
		$header = "/**\n";
		foreach ($arguments[$type] as $paramName => $paramType)
			$header .= '* @param {' . $paramType . '} ' . $paramName . "\n";
		$header .= "*/\n";
		$params   = (isset($config['params'])) ? $config['params'] : [];
		$callback = $this->getJavaScriptCallback($config);
		$js = '(' . \implode(',', \array_keys($arguments[$type])) . '){return ' . $callback . '(' . $this->buildCallbackArguments($params, $arguments[$type]) . ');}';
		$funcName = \sprintf('c%08X', \crc32($js));
		$js = $header . 'function ' . $funcName . $js . "\n";
		$this->callbacks[$funcName] = $js;
		return new Code($funcName);
	}
	protected function getJavaScriptCallback(array $callbackConfig)
	{
		if (isset($callbackConfig['js']))
			return '(' . $callbackConfig['js'] . ')';
		$callback = $callbackConfig['callback'];
		if (\is_string($callback))
			if (\substr($callback, 0, 41) === 's9e\\TextFormatter\\Parser\\BuiltInFilters::')
				return 'BuiltInFilters.' . \substr($callback, 41);
			elseif (\substr($callback, 0, 26) === 's9e\\TextFormatter\\Parser::')
				return \substr($callback, 26);
		return 'returnFalse';
	}
	protected function setRulesHints()
	{
		$this->hints['closeAncestor']   = 0;
		$this->hints['closeParent']     = 0;
		$this->hints['fosterParent']    = 0;
		$this->hints['requireAncestor'] = 0;
		$flags = 0;
		foreach ($this->config['tags'] as $tagConfig)
		{
			foreach (\array_intersect_key($tagConfig['rules'], $this->hints) as $k => $v)
				$this->hints[$k] = 1;
			$flags |= $tagConfig['rules']['flags'];
		}
		$flags |= $this->config['rootContext']['flags'];
		$parser = new ReflectionClass('s9e\\TextFormatter\\Parser');
		foreach ($parser->getConstants() as $constName => $constValue)
			if (\substr($constName, 0, 5) === 'RULE_')
				$this->hints[$constName] = ($flags & $constValue) ? 1 : 0;
	}
	protected function setTagAttributesHints(array $tagConfig)
	{
		if (empty($tagConfig['attributes']))
			return;
		foreach ($tagConfig['attributes'] as $attrConfig)
		{
			$this->hints['attributeGenerator']    |= isset($attrConfig['generator']);
			$this->hints['attributeDefaultValue'] |= isset($attrConfig['defaultValue']);
		}
	}
	protected function setTagsHints()
	{
		$this->hints['attributeGenerator']    = 0;
		$this->hints['attributeDefaultValue'] = 0;
		$this->hints['namespaces']            = 0;
		foreach ($this->config['tags'] as $tagName => $tagConfig)
		{
			$this->hints['namespaces'] |= (\strpos($tagName, ':') !== \false);
			$this->setTagAttributesHints($tagConfig);
		}
	}
	protected function setRenderingHints()
	{
		$this->hints['postProcessing'] = (int) (\strpos($this->xsl, 'data-s9e-livepreview-postprocess') !== \false);
	}
}