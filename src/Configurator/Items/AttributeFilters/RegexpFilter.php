<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
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
		$this->assessSafeness((string) $regexp);
	}

	/**
	* Assess the safeness of this attribute filter based on given regexp
	*
	* @param  string $filterRegexp
	* @return void
	*/
	protected function assessSafeness(string $filterRegexp): void
	{
		try
		{
			$regexp = RegexpParser::getAllowedCharacterRegexp($filterRegexp);
		}
		catch (Exception $e)
		{
			return;
		}

		// Test whether this regexp could allow any character that's disallowed in each context
		foreach (['AsURL', 'InCSS', 'InJS'] as $context)
		{
			$callback = ContextSafeness::class . '::getDisallowedCharacters' . $context;
			foreach ($callback() as $char)
			{
				if (preg_match($regexp, $char))
				{
					continue 2;
				}
			}

			$methodName = 'markAsSafe' . $context;
			$this->$methodName();
		}

		// Regexps that start with a fixed scheme are considered safe as URLs unless the regexp is
		// multiline. As a special case, we allow the scheme part to end with a single ? to allow
		// the regexp "https?"
		$regexp = '(^\\W\\^(?>\\((?:\\?:)?)*(?!data|\\w*script)\\w+\\??:.*\\W[a-ln-z]*+$)Dis';
		if (preg_match($regexp, $filterRegexp))
		{
			$this->markAsSafeAsURL();
		}
	}
}