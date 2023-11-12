<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

class OptimizeChooseAttributes extends AbstractChooseOptimization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = ['//xsl:choose[xsl:otherwise/xsl:attribute]'];

	/**
	* Get a list of all attribute elements common to all branches
	*
	* @return array<string, s9e\SweetDOM\Element>
	*/
	protected function getCommonAttributes(): array
	{
		$attributes         = [];
		$branchesAttributes = [];
		foreach ($this->getBranches() as $branch)
		{
			// Collect the XML for each xsl:attribute as well as a reference to the live node
			$branchAttributes = [];
			foreach ($branch->query('xsl:attribute') as $attribute)
			{
				$attrName                    = $attribute->getAttribute('name');
				$attributes[$attrName]       = $attribute;
				$branchAttributes[$attrName] = $attribute->ownerDocument->saveXML($attribute);
			}

			$branchesAttributes[] = $branchAttributes;
		}

		// Keep only attributes with an alphanumeric, literal name (no dynamic attributes)
		$attributes = array_filter(
			$attributes,
			fn($attrName) => preg_match('(^[-\\w]+$)', $attrName),
			ARRAY_FILTER_USE_KEY
		);

		// Keep only the attributes whose XML is identical in all branches, and match their name
		// to return an actual xsl:attribute element
		return array_intersect_key($attributes, array_intersect_assoc(...$branchesAttributes));
	}

	/**
	* {@inheritdoc}
	*/
	protected function optimizeChoose()
	{
		foreach ($this->getCommonAttributes() as $attrName => $attribute)
		{
			// Move the attribute before the xsl:choose element, then remove all remaining copies
			$this->choose->before($attribute);
			foreach ($this->choose->query('*/xsl:attribute[@name = "' . $attrName . '"]') as $xslAttribute)
			{
				$xslAttribute->remove();
			}
		}
	}
}