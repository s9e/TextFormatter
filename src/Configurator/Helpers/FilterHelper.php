<?php declare(strict_types=1);
/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Items\Filter;
use s9e\TextFormatter\Configurator\RecursiveParser;
abstract class FilterHelper
{
	protected static $parser;
	public static function getParser(): RecursiveParser
	{
		if (!isset(self::$parser))
		{
			self::$parser = new RecursiveParser;
			self::$parser->setMatchers([new FilterSyntaxMatcher(self::$parser)]);
		}
		return self::$parser;
	}
	public static function isAllowed(string $filter, array $allowedFilters): bool
	{
		if (\substr($filter, 0, 1) === '#')
			return \true;
		$filter = \trim(\preg_replace('(^\\\\|\\(.*)s', '', $filter));
		return \in_array($filter, $allowedFilters, \true);
	}
	public static function parse(string $filterString): array
	{
		$filterConfig           = self::getParser()->parse($filterString)['value'];
		$filterConfig['filter'] = \ltrim($filterConfig['filter'], '\\');
		return $filterConfig;
	}
}