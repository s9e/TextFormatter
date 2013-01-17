<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
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

		// Create a variant for this filter's config
		$regexp = $this->vars['regexp'];
		if (is_string($regexp))
		{
			$variant = new Variant($regexp);
			$variant->setDynamic(
				'JS',
				function () use ($regexp)
				{
					return RegexpConvertor::toJS($regexp);
				}
			);

			$this->vars['regexp'] = $variant;
		}

		// Generate this filter's config
		$config = parent::asConfig();

		// Restore the original value for 'regexp'
		$this->vars['regexp'] = $regexp;

		return $config;
	}

	/**
	* Set this filter's regexp
	*
	* @param  string $regexp PCRE regexp
	* @return void
	*/
	public function setRegexp($regexp)
	{
		if (@preg_match($regexp, '') === false)
		{
			throw new InvalidArgumentException('Invalid regular expression ' . var_export($regexp, true));
		}

		$this->vars['regexp'] = $regexp;
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

	/**
	* {@inheritdoc}
	*/
	public function isSafeInURL()
	{
		try
		{
			$regexp = RegexpParser::getAllowedCharacterRegexp($this->vars['regexp']);

			$allowedChars = '_-'
			              . implode('', range('0', '9'))
			              . implode('', range('A', 'Z'))
			              . implode('', range('a', 'z'));

			// Try the first 255 Unicode characters except 0-9, A-Z, a-z, _ and -
			$disallowedChars = count_chars($allowedChars, 4);

			foreach (str_split($disallowedChars, 1) as $char)
			{
				if (preg_match($regexp, utf8_encode($char)))
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