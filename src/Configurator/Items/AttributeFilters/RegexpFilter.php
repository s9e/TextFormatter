<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;

use Exception;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\ContextSafeness;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
use s9e\TextFormatter\Configurator\Items\Regexp;

class RegexpFilter extends AttributeFilter
{
	/**
	* Constructor
	*
	* @param  string $regexp PCRE regexp
	*/
	public function __construct($regexp = null)
	{
		parent::__construct('s9e\\TextFormatter\\Parser\\AttributeFilters\\RegexpFilter::filter');

		$this->resetParameters();
		$this->addParameterByName('attrValue');
		$this->addParameterByName('regexp');
		$this->setJS('RegexpFilter.filter');

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
			$regexp = new Regexp($regexp);
		}

		$this->vars['regexp'] = $regexp;
		$this->resetSafeness();
		$this->evaluateSafeness();
	}

	/**
	* Mark in which contexts values processed by this filter are safe to be used
	*
	* @return void
	*/
	protected function evaluateSafeness()
	{
		try
		{
			$this->evaluateSafenessAsURL();
			$this->evaluateSafenessInCSS();
			$this->evaluateSafenessInJS();
		}
		catch (Exception $e)
		{
			// If anything unexpected happens we don't try to mark this filter as safe
		}
	}

	/**
	* Mark whether this filter makes a value safe to be used as a URL
	*
	* @return void
	*/
	protected function evaluateSafenessAsURL()
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
			$this->markAsSafeAsURL();

			return;
		}

		// Test whether this regexp could allow any character that's disallowed in URLs
		$regexp = RegexpParser::getAllowedCharacterRegexp($this->vars['regexp']);
		foreach (ContextSafeness::getDisallowedCharactersAsURL() as $char)
		{
			if (preg_match($regexp, $char))
			{
				return;
			}
		}

		$this->markAsSafeAsURL();
	}

	/**
	* Mark whether this filter makes a value safe to be used in CSS
	*
	* @return void
	*/
	protected function evaluateSafenessInCSS()
	{
		// Test whether this regexp could allow any character that's disallowed in URLs
		$regexp = RegexpParser::getAllowedCharacterRegexp($this->vars['regexp']);
		foreach (ContextSafeness::getDisallowedCharactersInCSS() as $char)
		{
			if (preg_match($regexp, $char))
			{
				return;
			}
		}

		$this->markAsSafeInCSS();
	}

	/**
	* Mark whether this filter makes a value safe to be used in JS
	*
	* @return void
	*/
	protected function evaluateSafenessInJS()
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
		if (preg_match($regexp, $this->vars['regexp']))
		{
			$this->markAsSafeInJS();
		}
	}
}