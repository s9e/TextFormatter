<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

class CallbackGenerator
{
	/**
	* @var array Path to callbacks in keys, callback signature in values
	*/
	public $callbacks = [
		'tags.*.attributes.*.filterChain.*' => [
			'attrValue' => '*',
			'attrName'  => 'string'
		],
		'tags.*.filterChain.*' => [
			'tag'       => '!Tag',
			'tagConfig' => '!Object'
		]
	];

	/**
	* @var Encoder
	*/
	protected $encoder;

	/**
	* Constructor
	*/
	public function __construct()
	{
		$this->encoder = new Encoder;
	}

	/**
	* Replace all callbacks in given config
	*
	* @param  array $config Original config
	* @return array         Modified config
	*/
	public function replaceCallbacks(array $config)
	{
		foreach ($this->callbacks as $path => $params)
		{
			$config = $this->mapArray($config, explode('.', $path), $params);
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

		// Rebuild the local vars map to include global vars and computed values
		$available  = array_combine(array_keys($localVars), array_keys($localVars));
		$available += [
			'innerText'      => '(tag.getEndTag() ? text.substr(tag.getPos() + tag.getLen(), tag.getEndTag().getPos() - tag.getPos() - tag.getLen()) : "")',
			'logger'         => 'logger',
			'openTags'       => 'openTags',
			'outerText'      => 'text.substr(tag.getPos(), (tag.getEndTag() ? tag.getEndTag().getPos() + tag.getEndTag().getLen() - tag.getPos() : tag.getLen()))',
			'registeredVars' => 'registeredVars',
			'tagText'        => 'text.substr(tag.getPos(), tag.getLen())',
			'text'           => 'text'
		];

		$args = [];
		foreach ($params as $k => $v)
		{
			if (isset($v))
			{
				// Param by value
				$args[] = $this->encoder->encode($v);
			}
			elseif (isset($available[$k]))
			{
				// Param by name that matches a local expression
				$args[] = $available[$k];
			}
			else
			{
				$args[] = 'registeredVars[' . json_encode($k) . ']';
			}
		}

		return implode(',', $args);
	}

	/**
	* Generate a function from a callback config
	*
	* @param  array $config Callback config
	* @param  array $params Param names as keys, param types as values
	* @return Code
	*/
	protected function generateFunction(array $config, array $params)
	{
		$js = (string) $config['js'];

		// returnFalse() and returnTrue() can be used as-is
		if ($js === 'returnFalse' || $js === 'returnTrue')
		{
			return new Code($js);
		}

		// Add an empty list of params if none is set
		$config += ['params' => []];

		$src  = $this->getHeader($params);
		$src .= 'function(' . implode(',', array_keys($params)) . '){';
		$src .= 'return ' . $this->parenthesizeCallback($js);
		$src .= '(' . $this->buildCallbackArguments($config['params'], $params) . ');}';

		return new Code($src);
	}

	/**
	* Generate a function header for given signature
	*
	* @param  array  $params Param names as keys, param types as values
	* @return string
	*/
	protected function getHeader(array $params)
	{
		// Prepare the function's header
		$header = "/**\n";
		foreach ($params as $paramName => $paramType)
		{
			$header .= '* @param {' . $paramType . '} ' . $paramName . "\n";
		}
		$header .= "* @return {*}\n";
		$header .= "*/\n";

		return $header;
	}

	/**
	* Replace callbacks in given config array
	*
	* @param  array    $array  Original config
	* @param  string[] $path   Path to callbacks
	* @param  array    $params Default params
	* @return array            Modified config
	*/
	protected function mapArray(array $array, array $path, array $params)
	{
		$key  = array_shift($path);
		$keys = ($key === '*') ? array_keys($array) : [$key];
		foreach ($keys as $key)
		{
			if (!isset($array[$key]))
			{
				continue;
			}
			$array[$key] = (empty($path)) ? $this->generateFunction($array[$key], $params) : $this->mapArray($array[$key], $path, $params);
		}

		return $array;
	}

	/**
	* Add parentheses to a function literal, if necessary
	*
	* Will return single vars as-is, and will put anything else between parentheses
	*
	* @param  string $callback Original callback
	* @return string           Modified callback
	*/
	protected function parenthesizeCallback($callback)
	{
		return (preg_match('(^[.\\w]+$)D', $callback)) ? $callback : '(' . $callback  . ')';
	}
}