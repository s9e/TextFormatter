<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Regexp;
class SchemeList extends NormalizedList
{
	public function asConfig()
	{
		return new Regexp('/^' . RegexpBuilder::fromList($this->items) . '$/Di');
	}
	public function normalizeValue($scheme)
	{
		if (!\preg_match('#^[a-z][a-z0-9+\\-.]*$#Di', $scheme))
			throw new InvalidArgumentException("Invalid scheme name '" . $scheme . "'");
		return \strtolower($scheme);
	}
}