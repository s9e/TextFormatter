<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

use DOMException;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\UninlineAttributes
*/
class UninlineAttributesTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				'<div class="foo"><xsl:apply-templates/></div>',
				'<div><xsl:attribute name="class">foo</xsl:attribute><xsl:apply-templates/></div>'
			],
			[
				'<div class="foo" data-x="x"><xsl:apply-templates/></div>',
				'<div><xsl:attribute name="class">foo</xsl:attribute><xsl:attribute name="data-x">x</xsl:attribute><xsl:apply-templates/></div>'
			],
			[
				'<div class="{@foo} bar {@baz}"><xsl:apply-templates/></div>',
				'<div><xsl:attribute name="class"><xsl:value-of select="@foo"/> bar <xsl:value-of select="@baz"/></xsl:attribute><xsl:apply-templates/></div>'
			],
			[
				'<div class=" {@foo} "><xsl:apply-templates/></div>',
				'<div><xsl:attribute name="class"><xsl:text> </xsl:text><xsl:value-of select="@foo"/><xsl:text> </xsl:text></xsl:attribute><xsl:apply-templates/></div>'
			],
			[
				'<hr title=".."/>',
				'<hr><xsl:attribute name="title">..</xsl:attribute></hr>'
			],
			[
				'<hr title="&amp;&lt;&gt;&quot;"/>',
				'<hr><xsl:attribute name="title">&amp;&lt;&gt;"</xsl:attribute></hr>'
			],
			[
				'<hr/>',
				'<hr/>'
			],
			[
				'<hr title="{{{{}}}}"/>',
				'<hr><xsl:attribute name="title">{{}}</xsl:attribute></hr>'
			],
			[
				'<hr title="{{{{"/>',
				'<hr><xsl:attribute name="title">{{</xsl:attribute></hr>'
			],
			[
				'<hr title="}}}}"/>',
				'<hr><xsl:attribute name="title">}}</xsl:attribute></hr>'
			],
		];
	}
}