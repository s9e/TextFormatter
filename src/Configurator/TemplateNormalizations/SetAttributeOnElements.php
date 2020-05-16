<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

/**
* Set an attribute on matching elements
*/
class SetAttributeOnElements extends AddAttributeValueToElements
{
	protected function getValues(string $attrValue): array
	{
		return [];
	}
}