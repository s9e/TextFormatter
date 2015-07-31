<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;

use Exception;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\ContextSafeness;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
use s9e\TextFormatter\Configurator\Items\Regexp as RegexpObject;

class RegexpFilter extends AttributeFilter
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
			$regexp = '#^\\^' . $captureStart . '(?!data|\\w*script)[a-z0-9]+\\??:#i';
			if (preg_match($regexp, $regexpInfo['regexp'])
			 && strpos($regexpInfo['modifiers'], 'm') === false)
			{
				return true;
			}

			// Test whether this regexp could allow any character that's disallowed in URLs
			$regexp = RegexpParser::getAllowedCharacterRegexp($this->vars['regexp']);
			foreach (ContextSafeness::getDisallowedCharactersAsURL() as $char)
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

	/**
	* {@inheritdoc}
	*/
	public function isSafeInCSS()
	{
		try
		{
			// Test whether this regexp could allow any character that's disallowed in URLs
			$regexp = RegexpParser::getAllowedCharacterRegexp($this->vars['regexp']);
			foreach (ContextSafeness::getDisallowedCharactersInCSS() as $char)
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

	/**
	* {@inheritdoc}
	*/
	public function isSafeInJS()
	{
		$safeExpressions = [
			'\\d+',
			'[0-9]+'
		];

		// Ensure that the regexp is anchored with ^ and $, that it only contains a safe expression
		// optionally contained in a subpattern and that its modifiers contain PCRE_DOLLAR_ENDONLY
		// but no modifiers other than Dis
		$regexp = '(^(?<delim>.)\\^(?:'
		        . '(?<expr>' . implode('|', array_map('preg_quote', $safeExpressions)) . ')'
		        . '|'
		        . '\\((?:\\?[:>])?(?&expr)\\)'
		        . ')\\$(?&delim)(?=.*D)[Dis]*$)D';

		return (bool) preg_match($regexp, $this->vars['regexp']);
	}
}