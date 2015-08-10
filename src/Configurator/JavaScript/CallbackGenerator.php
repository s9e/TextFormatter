<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
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
			'attrName'  => '!string'
		],
		'tags.*.attributes.*.generator' => [
			'attrName'  => '!string'
		],
		'tags.*.filterChain.*' => [
			'tag'       => '!Tag',
			'tagConfig' => '!Object'
		]
	];

	/**
	* @var array Associative array of functions [name => function definition]
	*/
	protected $functions;

	/**
	* Constructor
	*/
	public function __construct()
	{
		$this->encoder = new Encoder;
	}

	/**
	* Return the functions created from the last replacements
	*
	* @return array Function names as keys, function definitions as values
	*/
	public function getFunctions()
	{
		ksort($this->functions);

		return $this->functions;
	}

	/**
	* Replace all callbacks in given config
	*
	* @param  array $config Original config
	* @return array         Modified config
	*/
	public function replaceCallbacks(array $config)
	{
		$this->functions = [];
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

		// Add global vars to the list of vars in scope
		$localVars += ['logger' => 1, 'openTags' => 1, 'registeredVars' => 1];

		$args = [];
		foreach ($params as $k => $v)
		{
			if (isset($v))
			{
				// Param by value
				$args[] = $this->encoder->encode($v);
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
	* Generate a function from a callback config
	*
	* @param  array $config Callback config
	* @param  array $params Param names as keys, param types as values
	* @return Code
	*/
	protected function generateFunction(array $config, array $params)
	{
		// Add an empty list of params if none is set
		$config += ['params' => []];

		// Start the source at the function signature, after the function name
		$src = '(' . implode(',', array_keys($params)) . '){return ';
		$src .= $this->parenthesizeCallback($config['js']);
		$src .= '(' . $this->buildCallbackArguments($config['params'], $params) . ');}';

		// Compute the function's name
		$funcName = sprintf('c%08X', crc32($src));

		// Prepend the function header and fill the missing part of the function definition
		$src = $this->getHeader($params) . 'function ' . $funcName . $src;

		// Save the function definition
		$this->functions[$funcName] = $src;

		return new Code($funcName);
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