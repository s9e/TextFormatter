<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\FilterableConfigValue;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;

class Regexp implements ConfigProvider, FilterableConfigValue
{
	/**
	* @var bool Whether this regexp should have the global flag set in JavaScript
	*/
	protected $isGlobal;

	/**
	* @var string JavaScript regexp, with delimiters and modifiers, e.g. "/foo/i"
	*/
	protected $jsRegexp;

	/**
	* @var string PCRE regexp, with delimiters and modifiers, e.g. "/foo/i"
	*/
	protected $regexp;

	/**
	* Constructor
	*
	* @param string $regexp   PCRE regexp, with delimiters and modifiers, e.g. "/foo/i"
	* @param bool   $isGlobal Whether this regexp should have the global flag set in JavaScript
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
		return $this;
	}

	/**
	* {@inheritdoc}
	*/
	public function filterConfig($target)
	{
		return ($target === 'JS') ? new Code($this->getJS()) : (string) $this;
	}

	/**
	* Return the name of each capture in this regexp
	*
	* @return string[]
	*/
	public function getCaptureNames()
	{
		return RegexpParser::getCaptureNames($this->regexp);
	}

	/**
	* Return this regexp's JavaScript representation
	*
	* @return string
	*/
	public function getJS()
	{
		if (!isset($this->jsRegexp))
		{
			$this->jsRegexp = RegexpConvertor::toJS($this->regexp, $this->isGlobal);
		}

		return $this->jsRegexp;
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

		// Prepare the start/end of the regexp and ensure that we use the D modifier
		$start = $regexpInfo['delimiter'] . '^';
		$end   = '$' . $regexpInfo['delimiter'] . $regexpInfo['modifiers'];
		if (strpos($regexpInfo['modifiers'], 'D') === false)
		{
			$end .= 'D';
		}

		foreach ($this->getNamedCapturesExpressions($regexpInfo['tokens']) as $name => $expr)
		{
			$captures[$name] = $start . $expr . $end;
		}

		return $captures;
	}

	/**
	* Return the expression used in each named capture
	*
	* @param  array[] $tokens
	* @return array
	*/
	protected function getNamedCapturesExpressions(array $tokens)
	{
		$exprs = [];
		foreach ($tokens as $token)
		{
			if ($token['type'] !== 'capturingSubpatternStart' || !isset($token['name']))
			{
				continue;
			}

			$expr = $token['content'];
			if (strpos($expr, '|') !== false)
			{
				$expr = '(?:' . $expr . ')';
			}

			$exprs[$token['name']] = $expr;
		}

		return $exprs;
	}

	/**
	* Set this regexp's JavaScript representation
	*
	* @param  string $jsRegexp
	* @return void
	*/
	public function setJS($jsRegexp)
	{
		$this->jsRegexp = $jsRegexp;
	}
}