<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use DOMXPath;
use Exception;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateLoader;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\TemplateHelper
*/
class TemplateHelperTest extends Test
{
	/**
	* @testdox getParametersFromXSL() tests
	* @dataProvider getParametersTests
	*/
	public function testGetParametersFromXSL($xsl, $expected)
	{
		if ($expected instanceof Exception)
		{
			$this->expectException(get_class($expected));
			$this->expectExceptionMessage($expected->getMessage());

		}

		$this->assertSame(
			$expected,
			TemplateHelper::getParametersFromXSL($xsl)
		);
	}

	public function getParametersTests()
	{
		return [
			[
				'',
				[]
			],
			[
				'<b><xsl:value-of select="concat($Foo, $BAR, $Foo)"/></b>',
				['BAR', 'Foo']
			],
			[
				'<b>
					<xsl:variable name="FOO"/>
					<xsl:value-of select="$FOO"/>
				</b>',
				[]
			],
			[
				'<b>
					<xsl:variable name="FOO"/>
					<xsl:if test="$BAR">
						<xsl:value-of select="$FOO"/>
					</xsl:if>
				</b>',
				['BAR']
			],
			[
				'<b>
					<xsl:value-of select="$FOO"/>
					<xsl:variable name="FOO"/>
					<xsl:if test="$BAR">
						<xsl:value-of select="$FOO"/>
					</xsl:if>
				</b>',
				['BAR', 'FOO']
			],
			[
				'<b title="$FOO{$BAR}$BAZ"/>',
				['BAR']
			],
			[
				'<b title="{concat($Foo, $BAR, $Foo)}"/>',
				['BAR', 'Foo']
			],
			[
				'<div>
					<xsl:variable name="S_TEST"/>
					<xsl:if test="$S_TEST">
						<b title="{$FOO}"/>
					</xsl:if>
				</div>',
				['FOO']
			],
			[
				'<div>
					<xsl:if test="$S_TEST">
						<b title="{$FOO}"/>
					</xsl:if>
					<xsl:variable name="S_TEST"/>
					<xsl:if test="$S_TEST">
						<b title="{$FOO}"/>
					</xsl:if>
				</div>',
				['FOO', 'S_TEST']
			],
		];
	}

	/**
	* @testdox highlightNode() tests
	* @dataProvider getHighlights
	*/
	public function testHighlightNode($query, $template, $expected)
	{
		$dom   = TemplateLoader::load($template);
		$xpath = new DOMXPath($dom);

		$this->assertSame(
			$expected,
			TemplateHelper::highlightNode(
				$xpath->query($query)->item(0),
				'<span style="background-color:#ff0">',
				'</span>'
			)
		);
	}

	public function getHighlights()
	{
		return [
			[
				'//xsl:apply-templates',
				'<script><xsl:apply-templates/></script>',
'&lt;script&gt;
  <span style="background-color:#ff0">&lt;xsl:apply-templates/&gt;</span>
&lt;/script&gt;'
			],
			[
				'//@href',
				'<a href="{@foo}"><xsl:apply-templates/></a>',
'&lt;a <span style="background-color:#ff0">href=&quot;{@foo}&quot;</span>&gt;
  &lt;xsl:apply-templates/&gt;
&lt;/a&gt;'
			],
			[
				'//processing-instruction()',
				'<?php foo(); ?>',
				'<span style="background-color:#ff0">&lt;?php foo(); ?&gt;</span>'
			],
			[
				'//comment()',
				'xx<!-- foo -->yy',
				'xx<span style="background-color:#ff0">&lt;!-- foo --&gt;</span>yy'
			],
			[
				'//text()',
				'<b>foo</b>',
				'&lt;b&gt;<span style="background-color:#ff0">foo</span>&lt;/b&gt;'
			],
			[
				'//script/xsl:apply-templates',
				'<b><xsl:apply-templates/></b><script><xsl:apply-templates/></script><i><xsl:apply-templates/></i>',
'&lt;b&gt;
  &lt;xsl:apply-templates/&gt;
&lt;/b&gt;
&lt;script&gt;
  <span style="background-color:#ff0">&lt;xsl:apply-templates/&gt;</span>
&lt;/script&gt;
&lt;i&gt;
  &lt;xsl:apply-templates/&gt;
&lt;/i&gt;'
			],
			[
				'//a[2]/@href',
				'<a href="{@foo}"><xsl:apply-templates/></a><a href="{@foo}"><xsl:apply-templates/></a>',
'&lt;a href=&quot;{@foo}&quot;&gt;
  &lt;xsl:apply-templates/&gt;
&lt;/a&gt;
&lt;a <span style="background-color:#ff0">href=&quot;{@foo}&quot;</span>&gt;
  &lt;xsl:apply-templates/&gt;
&lt;/a&gt;'
			],
			[
				'//processing-instruction()[2]',
				'<?php foo(); ?><?php foo(); ?><?php foo(); ?>',
'&lt;?php foo(); ?&gt;
<span style="background-color:#ff0">&lt;?php foo(); ?&gt;</span>
&lt;?php foo(); ?&gt;'
			],
			[
				'//comment()[2]',
				'xx<!-- foo --><!-- foo --><!-- foo -->yy',
				'xx&lt;!-- foo --&gt;<span style="background-color:#ff0">&lt;!-- foo --&gt;</span>&lt;!-- foo --&gt;yy'
			],
			[
				'//b[2]/text()',
				'<b>foo</b><b>foo</b><b>foo</b>',
'&lt;b&gt;foo&lt;/b&gt;
&lt;b&gt;<span style="background-color:#ff0">foo</span>&lt;/b&gt;
&lt;b&gt;foo&lt;/b&gt;'
			],
			[
				'//@onclick',
				'<hr onclick="&amp;{@foo}"/>',
				'&lt;hr <span style="background-color:#ff0">onclick=&quot;&amp;amp;{@foo}&quot;</span>/&gt;'
			],
		];
	}

	/**
	* @testdox replaceHomogeneousTemplates() tests
	* @dataProvider getReplaceHomogeneousTemplatesTests
	*/
	public function testReplaceHomogeneousTemplates($templates, $expected)
	{
		TemplateHelper::replaceHomogeneousTemplates($templates);
		$this->assertSame($expected, $templates);
	}

	public function getReplaceHomogeneousTemplatesTests()
	{
		return [
			[
				// Nothing happens if there's only one template
				[
					'p' => '<p><xsl:apply-templates/></p>'
				],
				[
					'p' => '<p><xsl:apply-templates/></p>'
				]
			],
			[
				[
					'b' => '<b><xsl:apply-templates/></b>',
					'i' => '<i><xsl:apply-templates/></i>',
					'u' => '<u><xsl:apply-templates/></u>'
				],
				[
					'b' => '<xsl:element name="{name()}"><xsl:apply-templates/></xsl:element>',
					'i' => '<xsl:element name="{name()}"><xsl:apply-templates/></xsl:element>',
					'u' => '<xsl:element name="{name()}"><xsl:apply-templates/></xsl:element>'
				]
			],
			[
				// Ensure we don't over-replace
				[
					'b' => '<b><xsl:apply-templates/></b>',
					'i' => '<i><xsl:apply-templates/></i>',
					'u' => '<u><xsl:apply-templates/></u>',
					'p' => '<p><xsl:apply-templates/></p>!'
				],
				[
					'b' => '<xsl:element name="{name()}"><xsl:apply-templates/></xsl:element>',
					'i' => '<xsl:element name="{name()}"><xsl:apply-templates/></xsl:element>',
					'u' => '<xsl:element name="{name()}"><xsl:apply-templates/></xsl:element>',
					'p' => '<p><xsl:apply-templates/></p>!'
				]
			],
			[
				// Test that names are lowercased
				[
					'B' => '<b><xsl:apply-templates/></b>',
					'I' => '<i><xsl:apply-templates/></i>',
					'p' => '<p><xsl:apply-templates/></p>'
				],
				[
					'B' => '<xsl:element name="{translate(name(),\'BI\',\'bi\')}"><xsl:apply-templates/></xsl:element>',
					'I' => '<xsl:element name="{translate(name(),\'BI\',\'bi\')}"><xsl:apply-templates/></xsl:element>',
					'p' => '<xsl:element name="{translate(name(),\'BI\',\'bi\')}"><xsl:apply-templates/></xsl:element>',
				]
			],
			[
				// Test namespaced tags
				[
					'html:b' => '<b><xsl:apply-templates/></b>',
					'html:i' => '<i><xsl:apply-templates/></i>',
					'html:u' => '<u><xsl:apply-templates/></u>'
				],
				[
					'html:b' => '<xsl:element name="{local-name()}"><xsl:apply-templates/></xsl:element>',
					'html:i' => '<xsl:element name="{local-name()}"><xsl:apply-templates/></xsl:element>',
					'html:u' => '<xsl:element name="{local-name()}"><xsl:apply-templates/></xsl:element>'
				]
			],
			[
				// Test namespaced tags
				[
					'html:b' => '<b><xsl:apply-templates/></b>',
					'html:I' => '<i><xsl:apply-templates/></i>',
					'html:u' => '<u><xsl:apply-templates/></u>'
				],
				[
					'html:b' => '<xsl:element name="{translate(local-name(),\'I\',\'i\')}"><xsl:apply-templates/></xsl:element>',
					'html:I' => '<xsl:element name="{translate(local-name(),\'I\',\'i\')}"><xsl:apply-templates/></xsl:element>',
					'html:u' => '<xsl:element name="{translate(local-name(),\'I\',\'i\')}"><xsl:apply-templates/></xsl:element>'
				]
			],
		];
	}
}