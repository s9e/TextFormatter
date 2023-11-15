<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\SweetDOM\Element;

/**
* De-optimize xsl:if elements so that xsl:choose dead branch elimination can apply to them
*/
class DeoptimizeIf extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = ['//xsl:if[@test]'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(Element $element): void
	{
		$choose = $element->replaceWithXslChoose();
		$when   = $choose->appendXslWhen($element->getAttribute('test'));
		$when->append(...$element->childNodes);
	}
}