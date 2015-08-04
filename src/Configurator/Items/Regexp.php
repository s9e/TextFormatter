<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;

class Regexp implements ConfigProvider
{
	/**
	* @var bool Whether this regexp should become a JavaScript RegExp object with global flag
	*/
	protected $isGlobal;

	/**
	* @var string PCRE regexp, with delimiters and modifiers, e.g. "/foo/i"
	*/
	protected $regexp;

	/**
	* Constructor
	*
	* @param  string $regexp PCRE regexp, with delimiters and modifiers, e.g. "/foo/i"
	* @return void
	*/
	public function __construct($regexp, $isGlobal = false)
	{
		if (@preg_match($regexp, '') === false)
		{
			throw new InvalidArgumentException('Invalid regular expression ' . var_export($regexp, true));
		}

		$this->regexp   = $regexp;
		$this->isGlobal = $isGlobal;
	}

	/**
	* Return this regexp as a string
	*
	* @return string
	*/
	public function __toString()
	{
		return $this->regexp;
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$variant = new Variant($this->regexp);
		$variant->setDynamic(
			'JS',
			function ()
			{
				return $this->toJS();
			}
		);

		return $variant;
	}

	/**
	* Return all the named captures with a standalone regexp that matches them
	*
	* @return array Array of [capture name => regexp]
	*/
	public function getNamedCaptures()
	{
		$captures   = [];
		$regexpInfo = RegexpParser::parse($this->regexp);

		// Ensure that we use the D modifier
		if (strpos($regexpInfo['modifiers'], 'D') === false)
		{
			$regexpInfo['modifiers'] .= 'D';
		}

		foreach ($regexpInfo['tokens'] as $token)
		{
			if ($token['type'] !== 'capturingSubpatternStart' || !isset($token['name']))
			{
				continue;
			}

			$name = $token['name'];
			if (!isset($captures[$name]))
			{
				$regexp = $regexpInfo['delimiter']
				        . '^(?:' . $token['content'] . ')$'
				        . $regexpInfo['delimiter']
				        . $regexpInfo['modifiers'];

				$captures[$name] = $regexp;
			}
		}

		return $captures;
	}

	/**
	* Return this regexp as a JavaScript RegExp
	*
	* @return RegExp
	*/
	public function toJS()
	{
		$obj = RegexpConvertor::toJS($this->regexp);

		if ($this->isGlobal)
		{
			$obj->flags .= 'g';
		}

		return $obj;
	}
}