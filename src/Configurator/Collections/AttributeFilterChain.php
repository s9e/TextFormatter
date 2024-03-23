<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use Override;
use s9e\TextFormatter\Configurator\Items\Filter;

class AttributeFilterChain extends FilterChain
{
	#[Override]
	protected function getDefaultFilter(string $filterName, array $constructorArgs = []): Filter
	{
		return AttributeFilterCollection::getDefaultFilter($filterName, $constructorArgs);
	}

	#[Override]
	public function getFilterClassName()
	{
		return 's9e\\TextFormatter\\Configurator\\Items\\AttributeFilter';
	}
}