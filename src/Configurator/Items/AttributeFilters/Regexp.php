<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
use s9e\TextFormatter\Configurator\Items\Regexp as RegexpObject;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;

class Regexp extends AttributeFilter
{
	/**
	* Constructor
	*
	* @param  string $regexp PCRE regexp
	* @return void
	*/
	public function __construct($regexp = null)
	{
		parent::__construct('s9e\\TextFormatter\\Parser\\BuiltInFilters::filterRegexp');

		$this->resetParameters();
		$this->addParameterByName('attrValue');
		$this->addParameterByName('regexp');
		$this->setJS('BuiltInFilters.filterRegexp');

		if (isset($regexp))
		{
			$this->setRegexp($regexp);
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		if (!isset($this->vars['regexp']))
		{
			throw new RuntimeException("Regexp filter is missing a 'regexp' value");
		}

		return parent::asConfig();
	}

	/**
	* Return this filter's regexp
	*
	* @return string
	*/
	public function getRegexp()
	{
		return (string) $this->vars['regexp'];
	}

	/**
	* Set this filter's regexp
	*
	* @param  string $regexp PCRE regexp
	* @return void
	*/
	public function setRegexp($regexp)
	{
		if (is_string($regexp))
		{
			$regexp = new RegexpObject($regexp);
		}

		$this->vars['regexp'] = $regexp;
	}

	/**
	* {@inheritdoc}
	*/
	public function isSafeAsURL()
	{
		try
		{
			$regexpInfo = RegexpParser::parse($this->vars['regexp']);

			// Match any number of "(" optionally followed by "?:"
			$captureStart = '(?>\\((?:\\?:)?)*';

			// Regexps that start with a fixed scheme are considered safe. As a special case, we
			// allow the scheme part to end with a single ? to allow the regexp "https?"
			$regexp = '#^\\^' . $captureStart . '(?!data|\\w*script)\\w+\\??:#i';
			if (preg_match($regexp, $regexpInfo['regexp'])
			 && strpos($regexpInfo['modifiers'], 'm') === false)
			{
				return true;
			}

			// Test whether this regexp could allow the use of a colon :
			if (preg_match(RegexpParser::getAllowedCharacterRegexp($this->vars['regexp']), ':'))
			{
				return false;
			}

			return true;
		}
		catch (Exception $e)
		{
			// If anything unexpected happens, we'll consider this filter is not safe
			return false;
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function isSafeInCSS()
	{
		try
		{
			$regexp = RegexpParser::getAllowedCharacterRegexp($this->vars['regexp']);

			// Test whether this regexp could allow any of the following characters
			$disallowedChars = "\\\"'():";
			foreach (str_split($disallowedChars, 1) as $char)
			{
				if (preg_match($regexp, $char))
				{
					return false;
				}
			}

			return true;
		}
		catch (Exception $e)
		{
			// If anything unexpected happens, we'll consider this filter is not safe
			return false;
		}
	}
}