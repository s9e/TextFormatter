<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;

/**
* Add a value to a list of space-separated value
*/
class AddAttributeValueToElements extends AbstractNormalization
{
	/**
	* @var string Name of the attribute to modify
	*/
	protected $attrName;

	/**
	* @var string Value to be added to the attribute
	*/
	protected $value;

	/**
	* @param string $query    XPath query used to locate elements
	* @param string $attrName Name of the attribute to modify
	* @param string $value    Value to be added to the attribute
	*/
	public function __construct(string $query, string $attrName, string $value)
	{
		$this->attrName = $attrName;
		$this->queries  = [$query];
		$this->value    = $value;
	}

	/**
	* Explode a string of space-separated values into an array
	*
	* @param  string   $attrValue Attribute's value
	* @return string[]
	*/
	protected function getValues(string $attrValue): array
	{
		return preg_match_all('(\\S++)', $attrValue, $m) ? $m[0] : [];
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element): void
	{
		$currentValues = $this->getValues($element->getAttribute($this->attrName));
		if (!in_array($this->value, $currentValues, true))
		{
			$currentValues[] = $this->value;

			$element->setAttribute($this->attrName, implode(' ', $currentValues));
		}
	}
}