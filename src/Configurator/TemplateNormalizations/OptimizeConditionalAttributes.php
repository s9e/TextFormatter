<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\SweetDOM\Element;

/**
* Optimize conditional attributes
*
* Will replace conditional attributes with a <xsl:copy-of/>, e.g.
*	<xsl:if test="@foo">
*		<xsl:attribute name="foo">
*			<xsl:value-of select="@foo" />
*		</xsl:attribute>
*	</xsl:if>
* into
*	<xsl:copy-of select="@foo"/>
*/
class OptimizeConditionalAttributes extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = ['//xsl:if[starts-with(@test, "@")][count(descendant::node()) = 2][xsl:attribute[@name = substring(../@test, 2)][xsl:value-of[@select = ../../@test]]]'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(Element $element): void
	{
		$element->replaceWithXslCopyOf($element->getAttribute('test'));
	}
}