<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

use RuntimeException;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\JavaScript\Encoder;

class Encoder
{
	/**
	* @var callable[]
	*/
	public $objectEncoders;

	/**
	* @var callable[]
	*/
	public $typeEncoders;

	/**
	* Constructor
	*
	* Will set up the default encoders
	*/
	public function __construct()
	{
		$ns = 's9e\\TextFormatter\\Configurator\\';
		$this->objectEncoders = [
			$ns . 'Items\\Regexp'           => [$this, 'encodeRegexp'],
			$ns . 'JavaScript\\Code'        => [$this, 'encodeCode'],
			$ns . 'JavaScript\\ConfigValue' => [$this, 'encodeConfigValue'],
			$ns . 'JavaScript\\Dictionary'  => [$this, 'encodeDictionary']
		];
		$this->typeEncoders = [
			'array'   => [$this, 'encodeArray'],
			'boolean' => [$this, 'encodeBoolean'],
			'double'  => [$this, 'encodeScalar'],
			'integer' => [$this, 'encodeScalar'],
			'object'  => [$this, 'encodeObject'],
			'string'  => [$this, 'encodeScalar']
		];
	}

	/**
	* Encode a value into JavaScript
	*
	* @param  mixed  $value
	* @return string
	*/
	public function encode($value)
	{
		$type = gettype($value);
		if (!isset($this->typeEncoders[$type]))
		{
			throw new RuntimeException('Cannot encode ' . $type . ' value');
		}

		return $this->typeEncoders[$type]($value);
	}

	/**
	* Encode an array to JavaScript
	*
	* @param  array  $array
	* @return string
	*/
	protected function encodeArray(array $array)
	{
		return ($this->isIndexedArray($array)) ? $this->encodeIndexedArray($array) : $this->encodeAssociativeArray($array);
	}

	/**
	* Encode an associative array to JavaScript
	*
	* @param  array  $array
	* @param  bool   $preserveNames
	* @return string
	*/
	protected function encodeAssociativeArray(array $array, $preserveNames = false)
	{
		ksort($array);
		$src = '{';
		$sep = '';
		foreach ($array as $k => $v)
		{
			$src .= $sep . $this->encodePropertyName("$k", $preserveNames) . ':' . $this->encode($v);
			$sep = ',';
		}
		$src .= '}';

		return $src;
	}

	/**
	* Encode a boolean value into JavaScript
	*
	* @param  bool   $value
	* @return string
	*/
	protected function encodeBoolean($value)
	{
		return ($value) ? '!0' : '!1';
	}

	/**
	* Encode a Code instance into JavaScript
	*
	* @param  Code   $code
	* @return string
	*/
	protected function encodeCode(Code $code)
	{
		return (string) $code;
	}

	/**
	* Encode a ConfigValue instance into JavaScript
	*
	* @param  ConfigValue $configValue
	* @return string
	*/
	protected function encodeConfigValue(ConfigValue $configValue)
	{
		return ($configValue->isDeduplicated()) ? $configValue->getVarName() : $this->encode($configValue->getValue());
	}

	/**
	* Encode a Dictionary object into a JavaScript object
	*
	* @param  Dictionary $dict
	* @return string
	*/
	protected function encodeDictionary(Dictionary $dict)
	{
		return $this->encodeAssociativeArray($dict->getArrayCopy(), true);
	}

	/**
	* Encode an indexed array to JavaScript
	*
	* @param  array  $array
	* @return string
	*/
	protected function encodeIndexedArray(array $array)
	{
		return '[' . implode(',', array_map([$this, 'encode'], $array)) . ']';
	}

	/**
	* Encode an object into JavaScript
	*
	* @param  object $object
	* @return string
	*/
	protected function encodeObject($object)
	{
		foreach ($this->objectEncoders as $className => $callback)
		{
			if ($object instanceof $className)
			{
				return $callback($object);
			}
		}

		throw new RuntimeException('Cannot encode instance of ' . get_class($object));
	}

	/**
	* Encode an object property name into JavaScript
	*
	* @param  string $name
	* @param  bool   $preserveNames
	* @return string
	*/
	protected function encodePropertyName($name, $preserveNames)
	{
		return ($preserveNames || !$this->isLegalProp($name)) ? json_encode($name) : $name;
	}

	/**
	* Encode a Regexp object into JavaScript
	*
	* @param  Regexp $regexp
	* @return string
	*/
	protected function encodeRegexp(Regexp $regexp)
	{
		return $regexp->getJS();
	}

	/**
	* Encode a scalar value into JavaScript
	*
	* @param  mixed  $value
	* @return string
	*/
	protected function encodeScalar($value)
	{
		return json_encode($value);
	}

	/**
	* Test whether given array is a numerically indexed array
	*
	* @param  array $array
	* @return bool
	*/
	protected function isIndexedArray(array $array)
	{
		if (empty($array))
		{
			return true;
		}

		if (isset($array[0]) && array_keys($array) === range(0, count($array) - 1))
		{
			return true;
		}

		return false;
	}

	/**
	* Test whether a string can be used as a property name, unquoted
	*
	* @link http://es5.github.io/#A.1
	*
	* @param  string $name Property's name
	* @return bool
	*/
	protected function isLegalProp($name)
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

		return (bool) preg_match('#^(?![0-9])[$_\\pL][$_\\pL\\pNl]+$#Du', $name);
	}
}