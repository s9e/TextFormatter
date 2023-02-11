<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\NormalizeUrls
*/
class NormalizeUrlsTest extends AbstractTestClass
{
	public static function getData()
	{
		return [
			[
				'<a href="http://example.org">xxx</a>',
				'<a href="http://example.org">xxx</a>'
			],
			[
				'<a href=" http://example.org/ ">xxx</a>',
				'<a href="http://example.org/">xxx</a>'
			],
			[
				'<a href="{@url}">xxx</a>',
				'<a href="{@url}">xxx</a>'
			],
			[
				'<a href="http://example.org/?foo[]=1">xxx</a>',
				'<a href="http://example.org/?foo%5B%5D=1">xxx</a>'
			],
			[
				'<a href="http://[fe80::a-en1]/?foo[]=1">xxx</a>',
				'<a href="http://[fe80::a-en1]/?foo%5B%5D=1">xxx</a>'
			],
			[
				'<a><xsl:attribute name="href">http://[fe80::a-en1]/?x[]=1</xsl:attribute>x</a>',
				'<a><xsl:attribute name="href">http://[fe80::a-en1]/?x%5B%5D=1</xsl:attribute>x</a>'
			],
			[
				'<a>
					<xsl:attribute name="href">
						<xsl:text>http://example.org/?foo[]=</xsl:text>
						<xsl:value-of select="@foo"/>
						<xsl:text>&amp;bar[]=2</xsl:text>
					</xsl:attribute>
					<xsl:apply-templates/>
				</a>',
				'<a>
					<xsl:attribute name="href">
						<xsl:text>http://example.org/?foo%5B%5D=</xsl:text>
						<xsl:value-of select="@foo"/>
						<xsl:text>&amp;bar%5B%5D=2</xsl:text>
					</xsl:attribute>
					<xsl:apply-templates/>
				</a>'
			],
			[
				'<a href="http://example.org/{{@url}}">xxx</a>',
				'<a href="http://example.org/%7B@url%7D">xxx</a>'
			],
		];
	}
}