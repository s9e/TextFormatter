<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Items\Filter;
use s9e\TextFormatter\Configurator\RecursiveParser;

abstract class FilterHelper
{
	/**
	* @var RecursiveParser
	*/
	protected static $parser;

	/**
	* Return the cached instance of RecursiveParser
	*
	* @return RecursiveParser
	*/
	public static function getParser(): RecursiveParser
	{
		if (!isset(self::$parser))
		{
			self::$parser = new RecursiveParser;
			self::$parser->setMatchers([new FilterSyntaxMatcher(self::$parser)]);
		}

		return self::$parser;
	}

	/**
	* Test whether given filter is a default filter or is in the list of allowed filters
	*
	* @param  string   $filter
	* @param  string[] $allowedFilters
	* @return bool
	*/
	public static function isAllowed(string $filter, array $allowedFilters): bool
	{
		if (substr($filter, 0, 1) === '#')
		{
			// Default filters are always allowed
			return true;
		}
		$filter = trim(preg_replace('(^\\\\|\\(.*)s', '', $filter));

		return in_array($filter, $allowedFilters, true);
	}

	/**
	* Parse a filter definition
	*
	* @param  string $filterString Filter definition such as "#number" or "strtolower($attrValue)"
	* @return array                Associative array with a "filter" element and optionally a
	*                              "params" array
	*/
	public static function parse(string $filterString): array
	{
		$filterConfig           = self::getParser()->parse($filterString)['value'];
		$filterConfig['filter'] = ltrim($filterConfig['filter'], '\\');

		return $filterConfig;
	}
}