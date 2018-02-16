<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use DOMDocument;
use DOMXPath;
use Exception;
use RuntimeException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\Template;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\TemplateHelper
*/
class TemplateHelperTest extends Test
{
	/**
	* @testdox loadTemplate() can load 'foo'
	*/
	public function testLoadText()
	{
		$text = 'foo';

		$dom = TemplateHelper::loadTemplate($text);
		$this->assertInstanceOf('DOMDocument', $dom);

		$this->assertContains($text, $dom->saveXML());
	}

	/**
	* @testdox saveTemplate() correctly handles 'foo'
	*/
	public function testSaveText()
	{
		$text = 'foo';

		$this->assertSame($text, TemplateHelper::saveTemplate(TemplateHelper::loadTemplate($text)));
	}

	/**
	* @testdox loadTemplate() can load '<xsl:value-of select="@foo"/>'
	*/
	public function testLoadXSL()
	{
		$xsl = '<xsl:value-of select="@foo"/>';

		$dom = TemplateHelper::loadTemplate($xsl);
		$this->assertInstanceOf('DOMDocument', $dom);

		$this->assertContains($xsl, $dom->saveXML());
	}

	/**
	* @testdox saveTemplate() correctly handles '<xsl:value-of select="@foo"/>'
	*/
	public function testSaveXSL()
	{
		$xsl = '<xsl:value-of select="@foo"/>';

		$this->assertSame($xsl, TemplateHelper::saveTemplate(TemplateHelper::loadTemplate($xsl)));
	}

	/**
	* @testdox saveTemplate() correctly handles an empty string
	*/
	public function testSaveXSLEmpty()
	{
		$xsl = '';

		$this->assertSame($xsl, TemplateHelper::saveTemplate(TemplateHelper::loadTemplate($xsl)));
	}

	/**
	* @testdox loadTemplate() can load '<ul><li>one<li>two</ul>'
	*/
	public function testLoadHTML()
	{
		$html = '<ul><li>one<li>two</ul>';
		$xml  = '<ul><li>one</li><li>two</li></ul>';

		$dom = TemplateHelper::loadTemplate($html);
		$this->assertInstanceOf('DOMDocument', $dom);

		$this->assertContains($xml, $dom->saveXML());
	}

	/**
	* @testdox loadTemplate() can load '<script>0<1 && 1>0 && alert(1)</script>'
	*/
	public function testLoadHTMLSpecialChars()
	{
		$html = '<script>0<1 && 1>0 && alert(1)</script>';
		$xml  = '<script><![CDATA[0<1 && 1>0 && alert(1)]]></script>';

		$dom = TemplateHelper::loadTemplate($html);
		$this->assertInstanceOf('DOMDocument', $dom);

		$this->assertContains($xml, $dom->saveXML());
	}

	/**
	* @testdox loadTemplate() can load '<ul><li>one<li>two</ul>'
	* @depends testLoadHTML
	*/
	public function testLoadHTMLInNamespace()
	{
		$html = '<ul><li>one<li>two</ul>';

		$this->assertSame(
			'http://www.w3.org/1999/XSL/Transform',
			TemplateHelper::loadTemplate($html)->lookupNamespaceURI('xsl')
		);
	}

	/**
	* @testdox loadTemplate() accepts unescaped ampersands
	*/
	public function testLoadTemplateAmpersands()
	{
		$template = '<a href="foo?bar=&baz="><xsl:apply-templates/></a>';
		$xml      = '<a href="foo?bar=&amp;baz="><xsl:apply-templates/></a>';

		$dom = TemplateHelper::loadTemplate($template);
		$this->assertInstanceOf('DOMDocument', $dom);

		$this->assertSame($xml, $dom->saveXML($dom->documentElement->firstChild));
	}

	/**
	* @testdox loadTemplate() converts HTML entities
	*/
	public function testLoadTemplateEntities()
	{
		$template = '<b title="&&eacute;;"><xsl:apply-templates/></b>';
		$xml      = '<b title="&amp;é;"><xsl:apply-templates/></b>';

		$dom = TemplateHelper::loadTemplate($template);
		$this->assertInstanceOf('DOMDocument', $dom);

		$this->assertSame($xml, $dom->saveXML($dom->documentElement->firstChild));
	}

	/**
	* @testdox loadTemplate() does not break numeric character references
	*/
	public function testLoadTemplateNumericCharacterReferences()
	{
		$template = '<b title="&&#x4C;&#x4f;&#76;;"><xsl:apply-templates/></b>';
		$xml      = '<b title="&amp;LOL;"><xsl:apply-templates/></b>';

		$dom = TemplateHelper::loadTemplate($template);
		$this->assertInstanceOf('DOMDocument', $dom);

		$this->assertSame($xml, $dom->saveXML($dom->documentElement->firstChild));
	}

	/**
	* @testdox loadTemplate() removes attributes with an invalid name
	*/
	public function testLoadTemplateAttributeInvalidName()
	{
		$template = '<div class="inline" padding:0>..</div>';
		$xml      = '<div class="inline">..</div>';

		$dom = TemplateHelper::loadTemplate($template);
		$this->assertInstanceOf('DOMDocument', $dom);

		$this->assertSame($xml, $dom->saveXML($dom->documentElement->firstChild));
	}

	/**
	* @testdox loadTemplate() removes attributes with an invalid namespace in XML
	*/
	public function testLoadTemplateAttributeInvalidNamespaceXML()
	{
		$template = '<div foo:bar:baz="1" title="" x:y:z="1">..</div>';

		$dom = TemplateHelper::loadTemplate($template);
		$xml = $dom->saveXML($dom->documentElement->firstChild);

		$this->assertInstanceOf('DOMDocument', $dom);
		$this->assertContains('title=""',       $xml);
		$this->assertNotContains('foo:bar:baz', $xml);
		$this->assertNotContains('x:y:z',       $xml);
	}

	/**
	* @testdox loadTemplate() removes attributes with an invalid namespace in HTML
	*/
	public function testLoadTemplateAttributeInvalidNamespaceHTML()
	{
		$template = '<div foo:bar:baz="1" title="" x:y:z="1"><br></div>';

		$dom = TemplateHelper::loadTemplate($template);
		$xml = $dom->saveXML($dom->documentElement->firstChild);

		$this->assertInstanceOf('DOMDocument', $dom);
		$this->assertContains('title=""',       $xml);
		$this->assertNotContains('foo:bar:baz', $xml);
		$this->assertNotContains('x:y:z',       $xml);
	}

	/**
	* @testdox saveTemplate() correctly handles '<ul><li>one<li>two</ul>'
	*/
	public function testSaveHTML()
	{
		$html = '<ul><li>one<li>two</ul>';
		$xml  = '<ul><li>one</li><li>two</li></ul>';

		$this->assertSame($xml, TemplateHelper::saveTemplate(TemplateHelper::loadTemplate($html)));
	}

	/**
	* @testdox loadTemplate() throws an exception on malformed XSL
	* @expectedException RuntimeException
	* @expectedExceptionMessage Invalid XSL: Premature end of data
	*/
	public function testLoadInvalidXSL()
	{
		$xsl = '<xsl:value-of select="@foo">';
		TemplateHelper::loadTemplate($xsl);
	}

	/**
	* @testdox loadTemplate() reads HTML as UTF-8
	*/
	public function testLoadUnicodeHTML()
	{
		$template = '<b title=foo>Pokémon</b>';
		$xml      = '<b title="foo">Pokémon</b>';

		$dom = TemplateHelper::loadTemplate($template);
		$this->assertInstanceOf('DOMDocument', $dom);

		$this->assertSame($xml, $dom->saveXML($dom->documentElement->firstChild));
	}

	/**
	* @testdox getParametersFromXSL() tests
	* @dataProvider getParametersTests
	*/
	public function testGetParametersFromXSL($xsl, $expected)
	{
		if ($expected instanceof Exception)
		{
			$this->setExpectedException(get_class($expected), $expected->getMessage());
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

	public function runTestGetNodes($methodName, $args, $template, $query)
	{
		$dom = new DOMDocument;
		$xsl = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $template
		     . '</xsl:template>';
		$dom->loadXML($xsl);

		if ($query)
		{
			$xpath    = new DOMXPath($dom);
			$expected = iterator_to_array($xpath->query($query), false);
		}
		else
		{
			$expected = [];
		}

		array_unshift($args, $dom);
		$actual = call_user_func_array('s9e\\TextFormatter\\Configurator\\Helpers\\TemplateHelper::' . $methodName, $args);

		$this->assertEquals(count($expected), count($actual), 'Wrong node count');

		$i   = -1;
		$cnt = count($expected);
		while (++$i < $cnt)
		{
			$this->assertTrue(
				$expected[$i]->isSameNode($actual[$i]),
				'Node ' . $i . ' does not match'
			);
		}
	}

	/**
	* @testdox getObjectParamsByRegexp() tests
	* @dataProvider getObjectParamsByRegexpTests
	*/
	public function testGetObjectParamsByRegexp($regexp, $template, $query = null)
	{
		$this->runTestGetNodes('getObjectParamsByRegexp', [$regexp], $template, $query);
	}

	/**
	* @testdox getCSSNodes() tests
	* @dataProvider getCSSNodesTests
	*/
	public function testGetCSSNodes($template, $query = null)
	{
		$this->runTestGetNodes('getCSSNodes', [], $template, $query);
	}

	/**
	* @testdox getJSNodes() tests
	* @dataProvider getJSNodesTests
	*/
	public function testGetJSNodes($template, $query = null)
	{
		$this->runTestGetNodes('getJSNodes', [], $template, $query);
	}

	/**
	* @testdox getURLNodes() tests
	* @dataProvider getURLNodesTests
	*/
	public function testGetURLNodes($template, $query = null)
	{
		$this->runTestGetNodes('getURLNodes', [], $template, $query);
	}

	public function getObjectParamsByRegexpTests()
	{
		return [
			[
				'//',
				'...',
				null
			],
			[
				'/^allowscriptaccess$/i',
				'<embed AllowScriptAccess="always"/>',
				'//@*'
			],
			[
				'/^allowscriptaccess$/i',
				'<div allowscriptaccess="always"/>',
				null
			],
			[
				'/^allowscriptaccess$/i',
				'<embed><xsl:attribute name="AllowScriptAccess"/></embed>',
				'//xsl:attribute'
			],
			[
				'/^allowscriptaccess$/i',
				'<embed><xsl:if test="@foo"><xsl:attribute name="AllowScriptAccess"/></xsl:if></embed>',
				'//xsl:attribute'
			],
			[
				'/^allowscriptaccess$/i',
				'<embed><xsl:copy-of select="@allowscriptaccess"/></embed>',
				'//xsl:copy-of'
			],
			[
				'/^allowscriptaccess$/i',
				'<object><param name="AllowScriptAccess"/><param name="foo"/></object>',
				'//param[@name != "foo"]'
			],
			[
				'/^allowscriptaccess$/i',
				'<object><xsl:if test="@foo"><param name="AllowScriptAccess"/><param name="foo"/></xsl:if></object>',
				'//param[@name != "foo"]'
			],
		];
	}

	public function getCSSNodesTests()
	{
		return [
			[
				'...'
			],
			[
				'<b style="1">...<i style="2">...</i></b><b style="3">...</b>',
				'//@style'
			],
			[
				'<b STYLE="">...</b>',
				'//@*'
			],
			[
				'<b><xsl:if test="@foo"><xsl:attribute name="style"/></xsl:if></b>',
				'//xsl:attribute'
			],
			[
				'<b><xsl:if test="@foo"><xsl:attribute name="STYLE"/></xsl:if></b>',
				'//xsl:attribute'
			],
			[
				'<b><xsl:copy-of select="@style"/></b>',
				'//xsl:copy-of'
			],
			[
				'<style/>',
				'*'
			],
			[
				'<STYLE/>',
				'*'
			],
			[
				'<xsl:element name="style"/>',
				'*'
			],
			[
				'<xsl:element name="STYLE"/>',
				'*'
			],
		];
	}

	public function getJSNodesTests()
	{
		return [
			[
				'...'
			],
			[
				'<script/>',
				'*'
			],
			[
				'<SCRIPT/>',
				'*'
			],
			[
				'<xsl:element name="script"/>',
				'*'
			],
			[
				'<xsl:element name="SCRIPT"/>',
				'*'
			],
			[
				'<b onclick=""/><i title=""/><b onfocus=""/>',
				'//@onclick | //@onfocus'
			],
			[
				'<b ONHOVER=""/>',
				'//@*'
			],
			[
				'<b><xsl:if test="@foo"><xsl:attribute name="onclick"/></xsl:if></b>',
				'//xsl:attribute'
			],
			[
				'<b><xsl:if test="@foo"><xsl:attribute name="ONCLICK"/></xsl:if></b>',
				'//xsl:attribute'
			],
			[
				'<b><xsl:copy-of select="@onclick"/></b>',
				'//xsl:copy-of'
			],
			[
				'<b data-s9e-livepreview-postprocess=""/>',
				'//@*'
			],
		];
	}

	public function getURLNodesTests()
	{
		return [
			[
				'...'
			],
			[
				'<form action=""/>',
				'//@action'
			],
			[
				'<body background=""/>',
				'//@background'
			],
			[
				'<blockquote cite=""/>',
				'//@cite',
			],
			[
				'<cite/>',
				null
			],
			[
				'<object classid=""/>',
				'//@classid'
			],
			[
				'<object codebase=""/>',
				'//@codebase'
			],
			[
				'<object data=""/>',
				'//@data'
			],
			[
				'<input formaction=""/>',
				'//@formaction'
			],
			[
				'<a href=""/>',
				'//@href'
			],
			[
				'<command icon=""/>',
				'//@icon'
			],
			[
				'<img longdesc=""/>',
				'//@longdesc'
			],
			[
				'<cache manifest=""/>',
				'//@manifest'
			],
			[
				'<head profile=""/>',
				'//@profile'
			],
			[
				'<video poster=""/>',
				'//@poster'
			],
			[
				'<img src=""/>',
				'//@src'
			],
			[
				'<img lowsrc=""/>',
				'//@lowsrc'
			],
			[
				'<img dynsrc=""/>',
				'//@dynsrc'
			],
			[
				'<input usemap=""/>',
				'//@usemap'
			],
			[
				'<object><param name="movie" value=""/></object>',
				'//@value'
			],
			[
				'<OBJECT><PARAM NAME="MOVIE" VALUE=""/></OBJECT>',
				'//@value'
			],
			[
				'<object><param name="dataurl" value=""/></object>',
				'//@value'
			],
		];
	}

	/**
	* @testdox getElementsByRegexp() can return elements created via <xsl:copy-of/>
	*/
	public function testGetElementsByRegexp()
	{
		$dom = TemplateHelper::loadTemplate('<xsl:copy-of select="x"/><xsl:copy-of select="foo"/>');

		$this->assertSame(
			[$dom->firstChild->firstChild->nextSibling],
			TemplateHelper::getElementsByRegexp($dom, '/^foo$/')
		);
	}

	/**
	* @testdox replaceTokens() tests
	* @dataProvider replaceTokensTests
	*/
	public function testReplaceTokens($template, $regexp, $fn, $expected)
	{
		if ($expected instanceof Exception)
		{
			$this->setExpectedException(get_class($expected), $expected->getMessage());
		}

		$this->assertSame(
			$expected,
			TemplateHelper::replaceTokens($template, $regexp, $fn, $expected)
		);
	}

	public function replaceTokensTests()
	{
		return [
			[
				'',
				'/foo/',
				function ($m) {},
				''
			],
			[
				'<br/>',
				'/foo/',
				function ($m) {},
				'<br/>'
			],
			[
				'<b title="$1" alt="$2"/>',
				'/\\$[0-9]+/',
				function ($m)
				{
					return ['literal', serialize($m)];
				},
				'<b title="a:1:{i:0;s:2:&quot;$1&quot;;}" alt="a:1:{i:0;s:2:&quot;$2&quot;;}"/>'
			],
			[
				'<b title="$1"/>',
				'/\\$[0-9]+/',
				function ($m)
				{
					return ['expression', '@foo'];
				},
				'<b title="{@foo}"/>'
			],
			[
				'<b title="$1"/>',
				'/\\$[0-9]+/',
				function ($m)
				{
					return ['passthrough'];
				},
				'<b title="{.}"/>'
			],
			[
				'<b>$1</b>',
				'/\\$[0-9]+/',
				function ($m)
				{
					return ['literal', serialize($m)];
				},
				'<b>a:1:{i:0;s:2:"$1";}</b>'
			],
			[
				'<b>$1</b>',
				'/\\$[0-9]+/',
				function ($m)
				{
					return ['expression', '@foo'];
				},
				'<b><xsl:value-of select="@foo"/></b>'
			],
			[
				'<b>$1</b>',
				'/\\$[0-9]+/',
				function ($m)
				{
					return ['passthrough'];
				},
				'<b><xsl:apply-templates/></b>'
			],
			[
				'<b id="$1">$1</b>',
				'/\\$[0-9]+/',
				function ($m, $node)
				{
					return ['literal', get_class($node)];
				},
				'<b id="DOMAttr">DOMText</b>'
			],
			[
				'<b>$1</b><i>$$</i>',
				'/\\$[0-9]+/',
				function ($m)
				{
					return ['literal', 'ONE'];
				},
				'<b>ONE</b><i>$$</i>'
			],
			[
				'<b>foo $1 bar</b>',
				'/\\$[0-9]+/',
				function ($m)
				{
					return ['literal', 'ONE'];
				},
				'<b>foo ONE bar</b>'
			],
			[
				'<b>xx</b>',
				'/x/',
				function ($m)
				{
					return ['literal', 'X'];
				},
				'<b>XX</b>'
			],
			[
				'<b>.x.x.</b>',
				'/x/',
				function ($m)
				{
					return ['literal', 'X'];
				},
				'<b>.X.X.</b>'
			],
		];
	}

	/**
	* @testdox highlightNode() tests
	* @dataProvider getHighlights
	*/
	public function testHighlightNode($query, $template, $expected)
	{
		$dom   = TemplateHelper::loadTemplate($template);
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