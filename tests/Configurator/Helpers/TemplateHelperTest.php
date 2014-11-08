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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\InvalidXslException
	* @expectedExceptionMessage Premature end of data
	*/
	public function testLoadInvalidXSL()
	{
		$xsl = '<xsl:value-of select="@foo">';
		TemplateHelper::loadTemplate($xsl);
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
		return array(
			array(
				'',
				array()
			),
			array(
				'<b><xsl:value-of select="concat($Foo, $BAR, $Foo)"/></b>',
				array('BAR', 'Foo')
			),
			array(
				'<b>
					<xsl:variable name="FOO"/>
					<xsl:value-of select="$FOO"/>
				</b>',
				array()
			),
			array(
				'<b>
					<xsl:variable name="FOO"/>
					<xsl:if test="$BAR">
						<xsl:value-of select="$FOO"/>
					</xsl:if>
				</b>',
				array('BAR')
			),
			array(
				'<b>
					<xsl:value-of select="$FOO"/>
					<xsl:variable name="FOO"/>
					<xsl:if test="$BAR">
						<xsl:value-of select="$FOO"/>
					</xsl:if>
				</b>',
				array('BAR', 'FOO')
			),
			array(
				'<b title="$FOO{$BAR}$BAZ"/>',
				array('BAR')
			),
			array(
				'<b title="{concat($Foo, $BAR, $Foo)}"/>',
				array('BAR', 'Foo')
			),
			array(
				'<div>
					<xsl:variable name="S_TEST"/>
					<xsl:if test="$S_TEST">
						<b title="{$FOO}"/>
					</xsl:if>
				</div>',
				array('FOO')
			),
			array(
				'<div>
					<xsl:if test="$S_TEST">
						<b title="{$FOO}"/>
					</xsl:if>
					<xsl:variable name="S_TEST"/>
					<xsl:if test="$S_TEST">
						<b title="{$FOO}"/>
					</xsl:if>
				</div>',
				array('FOO', 'S_TEST')
			),
		);
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
			$expected = array();
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
		$this->runTestGetNodes('getObjectParamsByRegexp', array($regexp), $template, $query);
	}

	/**
	* @testdox getCSSNodes() tests
	* @dataProvider getCSSNodesTests
	*/
	public function testGetCSSNodes($template, $query = null)
	{
		$this->runTestGetNodes('getCSSNodes', array(), $template, $query);
	}

	/**
	* @testdox getJSNodes() tests
	* @dataProvider getJSNodesTests
	*/
	public function testGetJSNodes($template, $query = null)
	{
		$this->runTestGetNodes('getJSNodes', array(), $template, $query);
	}

	/**
	* @testdox getURLNodes() tests
	* @dataProvider getURLNodesTests
	*/
	public function testGetURLNodes($template, $query = null)
	{
		$this->runTestGetNodes('getURLNodes', array(), $template, $query);
	}

	public function getObjectParamsByRegexpTests()
	{
		return array(
			array(
				'//',
				'...',
				null
			),
			array(
				'/^allowscriptaccess$/i',
				'<embed AllowScriptAccess="always"/>',
				'//@*'
			),
			array(
				'/^allowscriptaccess$/i',
				'<div allowscriptaccess="always"/>',
				null
			),
			array(
				'/^allowscriptaccess$/i',
				'<embed><xsl:attribute name="AllowScriptAccess"/></embed>',
				'//xsl:attribute'
			),
			array(
				'/^allowscriptaccess$/i',
				'<embed><xsl:if test="@foo"><xsl:attribute name="AllowScriptAccess"/></xsl:if></embed>',
				'//xsl:attribute'
			),
			array(
				'/^allowscriptaccess$/i',
				'<embed><xsl:copy-of select="@allowscriptaccess"/></embed>',
				'//xsl:copy-of'
			),
			array(
				'/^allowscriptaccess$/i',
				'<object><param name="AllowScriptAccess"/><param name="foo"/></object>',
				'//param[@name != "foo"]'
			),
			array(
				'/^allowscriptaccess$/i',
				'<object><xsl:if test="@foo"><param name="AllowScriptAccess"/><param name="foo"/></xsl:if></object>',
				'//param[@name != "foo"]'
			),
		);
	}

	public function getCSSNodesTests()
	{
		return array(
			array(
				'...'
			),
			array(
				'<b style="1">...<i style="2">...</i></b><b style="3">...</b>',
				'//@style'
			),
			array(
				'<b STYLE="">...</b>',
				'//@*'
			),
			array(
				'<b><xsl:if test="@foo"><xsl:attribute name="style"/></xsl:if></b>',
				'//xsl:attribute'
			),
			array(
				'<b><xsl:if test="@foo"><xsl:attribute name="STYLE"/></xsl:if></b>',
				'//xsl:attribute'
			),
			array(
				'<b><xsl:copy-of select="@style"/></b>',
				'//xsl:copy-of'
			),
			array(
				'<style/>',
				'*'
			),
			array(
				'<STYLE/>',
				'*'
			),
			array(
				'<xsl:element name="style"/>',
				'*'
			),
			array(
				'<xsl:element name="STYLE"/>',
				'*'
			),
		);
	}

	public function getJSNodesTests()
	{
		return array(
			array(
				'...'
			),
			array(
				'<script/>',
				'*'
			),
			array(
				'<SCRIPT/>',
				'*'
			),
			array(
				'<xsl:element name="script"/>',
				'*'
			),
			array(
				'<xsl:element name="SCRIPT"/>',
				'*'
			),
			array(
				'<b onclick=""/><i title=""/><b onfocus=""/>',
				'//@onclick | //@onfocus'
			),
			array(
				'<b ONHOVER=""/>',
				'//@*'
			),
			array(
				'<b><xsl:if test="@foo"><xsl:attribute name="onclick"/></xsl:if></b>',
				'//xsl:attribute'
			),
			array(
				'<b><xsl:if test="@foo"><xsl:attribute name="ONCLICK"/></xsl:if></b>',
				'//xsl:attribute'
			),
			array(
				'<b><xsl:copy-of select="@onclick"/></b>',
				'//xsl:copy-of'
			),
			array(
				'<b data-s9e-livepreview-postprocess=""/>',
				'//@*'
			),
		);
	}

	public function getURLNodesTests()
	{
		return array(
			array(
				'...'
			),
			array(
				'<form action=""/>',
				'//@action'
			),
			array(
				'<body background=""/>',
				'//@background'
			),
			array(
				'<blockquote cite=""/>',
				'//@cite',
			),
			array(
				'<cite/>',
				null
			),
			array(
				'<object classid=""/>',
				'//@classid'
			),
			array(
				'<object codebase=""/>',
				'//@codebase'
			),
			array(
				'<object data=""/>',
				'//@data'
			),
			array(
				'<input formaction=""/>',
				'//@formaction'
			),
			array(
				'<a href=""/>',
				'//@href'
			),
			array(
				'<command icon=""/>',
				'//@icon'
			),
			array(
				'<img longdesc=""/>',
				'//@longdesc'
			),
			array(
				'<cache manifest=""/>',
				'//@manifest'
			),
			array(
				'<head profile=""/>',
				'//@profile'
			),
			array(
				'<video poster=""/>',
				'//@poster'
			),
			array(
				'<img src=""/>',
				'//@src'
			),
			array(
				'<img lowsrc=""/>',
				'//@lowsrc'
			),
			array(
				'<img dynsrc=""/>',
				'//@dynsrc'
			),
			array(
				'<input usemap=""/>',
				'//@usemap'
			),
			array(
				'<object><param name="movie" value=""/></object>',
				'//@value'
			),
			array(
				'<OBJECT><PARAM NAME="MOVIE" VALUE=""/></OBJECT>',
				'//@value'
			),
			array(
				'<object><param name="dataurl" value=""/></object>',
				'//@value'
			),
		);
	}

	/**
	* @testdox getElementsByRegexp() can return elements created via <xsl:copy-of/>
	*/
	public function testGetElementsByRegexp()
	{
		$dom = TemplateHelper::loadTemplate('<xsl:copy-of select="x"/><xsl:copy-of select="foo"/>');

		$this->assertSame(
			array($dom->firstChild->firstChild->nextSibling),
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
		return array(
			array(
				'',
				'/foo/',
				function ($m) {},
				''
			),
			array(
				'<br/>',
				'/foo/',
				function ($m) {},
				'<br/>'
			),
			array(
				'<b title="$1" alt="$2"/>',
				'/\\$[0-9]+/',
				function ($m)
				{
					return array('literal', serialize($m));
				},
				'<b title="a:1:{i:0;s:2:&quot;$1&quot;;}" alt="a:1:{i:0;s:2:&quot;$2&quot;;}"/>'
			),
			array(
				'<b title="$1"/>',
				'/\\$[0-9]+/',
				function ($m)
				{
					return array('expression', '@foo');
				},
				'<b title="{@foo}"/>'
			),
			array(
				'<b title="$1"/>',
				'/\\$[0-9]+/',
				function ($m)
				{
					return array('passthrough', true);
				},
				'<b title="{.}"/>'
			),
			array(
				'<b title="$1"/>',
				'/\\$[0-9]+/',
				function ($m)
				{
					return array('passthrough', false);
				},
				'<b title="{substring(.,1+string-length(st),string-length()-(string-length(st)+string-length(et)))}"/>'
			),
			array(
				'<b>$1</b>',
				'/\\$[0-9]+/',
				function ($m)
				{
					return array('literal', serialize($m));
				},
				'<b>a:1:{i:0;s:2:"$1";}</b>'
			),
			array(
				'<b>$1</b>',
				'/\\$[0-9]+/',
				function ($m)
				{
					return array('expression', '@foo');
				},
				'<b><xsl:value-of select="@foo"/></b>'
			),
			array(
				'<b>$1</b>',
				'/\\$[0-9]+/',
				function ($m)
				{
					return array('passthrough', true);
				},
				'<b><xsl:apply-templates/></b>'
			),
			array(
				'<b>$1</b>',
				'/\\$[0-9]+/',
				function ($m)
				{
					return array('passthrough', false);
				},
				'<b><xsl:apply-templates/></b>'
			),
			array(
				'<b id="$1">$1</b>',
				'/\\$[0-9]+/',
				function ($m, $node)
				{
					return array('literal', get_class($node));
				},
				'<b id="DOMAttr">DOMText</b>'
			),
			array(
				'<b>$1</b><i>$$</i>',
				'/\\$[0-9]+/',
				function ($m)
				{
					return array('literal', 'ONE');
				},
				'<b>ONE</b><i>$$</i>'
			),
			array(
				'<b>foo $1 bar</b>',
				'/\\$[0-9]+/',
				function ($m)
				{
					return array('literal', 'ONE');
				},
				'<b>foo ONE bar</b>'
			),
		);
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
		return array(
			array(
				'//xsl:apply-templates',
				'<script><xsl:apply-templates/></script>',
'&lt;script&gt;
  <span style="background-color:#ff0">&lt;xsl:apply-templates/&gt;</span>
&lt;/script&gt;'
			),
			array(
				'//@href',
				'<a href="{@foo}"><xsl:apply-templates/></a>',
'&lt;a <span style="background-color:#ff0">href=&quot;{@foo}&quot;</span>&gt;
  &lt;xsl:apply-templates/&gt;
&lt;/a&gt;'
			),
			array(
				'//processing-instruction()',
				'<?php foo(); ?>',
				'<span style="background-color:#ff0">&lt;?php foo(); ?&gt;</span>'
			),
			array(
				'//comment()',
				'xx<!-- foo -->yy',
				'xx<span style="background-color:#ff0">&lt;!-- foo --&gt;</span>yy'
			),
			array(
				'//text()',
				'<b>foo</b>',
				'&lt;b&gt;<span style="background-color:#ff0">foo</span>&lt;/b&gt;'
			),
			array(
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
			),
			array(
				'//a[2]/@href',
				'<a href="{@foo}"><xsl:apply-templates/></a><a href="{@foo}"><xsl:apply-templates/></a>',
'&lt;a href=&quot;{@foo}&quot;&gt;
  &lt;xsl:apply-templates/&gt;
&lt;/a&gt;
&lt;a <span style="background-color:#ff0">href=&quot;{@foo}&quot;</span>&gt;
  &lt;xsl:apply-templates/&gt;
&lt;/a&gt;'
			),
			array(
				'//processing-instruction()[2]',
				'<?php foo(); ?><?php foo(); ?><?php foo(); ?>',
'&lt;?php foo(); ?&gt;
<span style="background-color:#ff0">&lt;?php foo(); ?&gt;</span>
&lt;?php foo(); ?&gt;'
			),
			array(
				'//comment()[2]',
				'xx<!-- foo --><!-- foo --><!-- foo -->yy',
				'xx&lt;!-- foo --&gt;<span style="background-color:#ff0">&lt;!-- foo --&gt;</span>&lt;!-- foo --&gt;yy'
			),
			array(
				'//b[2]/text()',
				'<b>foo</b><b>foo</b><b>foo</b>',
'&lt;b&gt;foo&lt;/b&gt;
&lt;b&gt;<span style="background-color:#ff0">foo</span>&lt;/b&gt;
&lt;b&gt;foo&lt;/b&gt;'
			),
		);
	}

	/**
	* @testdox getMetaElementsRegexp() tests
	* @dataProvider getMetaElementsRegexpTests
	*/
	public function testMetaElementsRegexp(array $templates, $expected)
	{
		$this->assertSame($expected, TemplateHelper::getMetaElementsRegexp($templates));
	}

	public function getMetaElementsRegexpTests()
	{
		return array(
			array(
				array(),
				'(<[eis]>[^<]*</[^>]+>)'
			),
			array(
				array('e' => '', 'i' => '', 's' => '', 'B' => '<b>..</b>'),
				'(<[eis]>[^<]*</[^>]+>)'
			),
			array(
				array('e' => '<xsl:value-of select="."/>', 'i' => '', 's' => '', 'B' => '<b>..</b>'),
				'(<[is]>[^<]*</[^>]+>)'
			),
			array(
				array('e' => '.', 'i' => '.', 's' => '.', 'B' => '<b>..</b>'),
				'((?!))'
			),
			array(
				array('X' => '<xsl:value-of select="$s"/>'),
				'(<[eis]>[^<]*</[^>]+>)'
			),
			array(
				array('X' => '<xsl:value-of select="@s"/>'),
				'(<[eis]>[^<]*</[^>]+>)'
			),
			array(
				array('X' => '<xsl:value-of select="s"/>'),
				'(<[ei]>[^<]*</[^>]+>)'
			),
			array(
				array('X' => '<xsl:if test="e">...</xsl:if>'),
				'(<[is]>[^<]*</[^>]+>)'
			),
			array(
				array('X' => '<hr title="s{i}e"/>'),
				'(<[es]>[^<]*</[^>]+>)'
			),
		);
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
		return array(
			array(
				// Nothing happens if there's only one template
				array(
					'p' => '<p><xsl:apply-templates/></p>'
				),
				array(
					'p' => '<p><xsl:apply-templates/></p>'
				)
			),
			array(
				array(
					'b' => '<b><xsl:apply-templates/></b>',
					'i' => '<i><xsl:apply-templates/></i>',
					'u' => '<u><xsl:apply-templates/></u>'
				),
				array(
					'b' => '<xsl:element name="{name()}"><xsl:apply-templates/></xsl:element>',
					'i' => '<xsl:element name="{name()}"><xsl:apply-templates/></xsl:element>',
					'u' => '<xsl:element name="{name()}"><xsl:apply-templates/></xsl:element>'
				)
			),
			array(
				// Ensure we don't over-replace
				array(
					'b' => '<b><xsl:apply-templates/></b>',
					'i' => '<i><xsl:apply-templates/></i>',
					'u' => '<u><xsl:apply-templates/></u>',
					'p' => '<p><xsl:apply-templates/></p>!'
				),
				array(
					'b' => '<xsl:element name="{name()}"><xsl:apply-templates/></xsl:element>',
					'i' => '<xsl:element name="{name()}"><xsl:apply-templates/></xsl:element>',
					'u' => '<xsl:element name="{name()}"><xsl:apply-templates/></xsl:element>',
					'p' => '<p><xsl:apply-templates/></p>!'
				)
			),
			array(
				// Test that names are lowercased
				array(
					'B' => '<b><xsl:apply-templates/></b>',
					'I' => '<i><xsl:apply-templates/></i>',
					'p' => '<p><xsl:apply-templates/></p>'
				),
				array(
					'B' => '<xsl:element name="{translate(name(),\'BI\',\'bi\')}"><xsl:apply-templates/></xsl:element>',
					'I' => '<xsl:element name="{translate(name(),\'BI\',\'bi\')}"><xsl:apply-templates/></xsl:element>',
					'p' => '<xsl:element name="{translate(name(),\'BI\',\'bi\')}"><xsl:apply-templates/></xsl:element>',
				)
			),
			array(
				// Test namespaced tags
				array(
					'html:b' => '<b><xsl:apply-templates/></b>',
					'html:i' => '<i><xsl:apply-templates/></i>',
					'html:u' => '<u><xsl:apply-templates/></u>'
				),
				array(
					'html:b' => '<xsl:element name="{local-name()}"><xsl:apply-templates/></xsl:element>',
					'html:i' => '<xsl:element name="{local-name()}"><xsl:apply-templates/></xsl:element>',
					'html:u' => '<xsl:element name="{local-name()}"><xsl:apply-templates/></xsl:element>'
				)
			),
			array(
				// Test namespaced tags
				array(
					'html:b' => '<b><xsl:apply-templates/></b>',
					'html:I' => '<i><xsl:apply-templates/></i>',
					'html:u' => '<u><xsl:apply-templates/></u>'
				),
				array(
					'html:b' => '<xsl:element name="{translate(local-name(),\'I\',\'i\')}"><xsl:apply-templates/></xsl:element>',
					'html:I' => '<xsl:element name="{translate(local-name(),\'I\',\'i\')}"><xsl:apply-templates/></xsl:element>',
					'html:u' => '<xsl:element name="{translate(local-name(),\'I\',\'i\')}"><xsl:apply-templates/></xsl:element>'
				)
			),
		);
	}
}