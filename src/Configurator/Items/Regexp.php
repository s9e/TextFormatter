<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;

class Regexp extends Variant implements ConfigProvider
{
	/**
	* @var bool Whether this regexp should have the global flag set in JavaScript
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
	*/
	public function __construct($regexp, $isGlobal = false)
	{
		if (@preg_match($regexp, '') === false)
		{
			throw new InvalidArgumentException('Invalid regular expression ' . var_export($regexp, true));
		}

		parent::__construct($regexp);
		$this->setDynamic(
			'JS',
			function ()
			{
				return $this->toJS();
			}
		);

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
	* Return the name of each capture in this regexp
	*
	* @return string[]
	*/
	public function getCaptureNames()
	{
		return RegexpParser::getCaptureNames($this->regexp);
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
	* Return this regexp as JavaScript code
	*
	* @return \s9e\TextFormatter\Configurator\JavaScript\Code
	*/
	public function toJS()
	{
		return RegexpConvertor::toJS($this->regexp, $this->isGlobal);
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
}