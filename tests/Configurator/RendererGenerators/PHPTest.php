<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators;

use DOMDocument;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Items\UnsafeTemplate;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP
*/
class PHPTest extends Test
{
	protected function getRendererFromXsl($xsl)
	{
		$className = 'Renderer_' . md5($xsl);

		if (!class_exists($className, false))
		{
			$generator = new PHP($className);
			eval($generator->generate($xsl));
		}

		return new $className;
	}

	/**
	* @testdox Returns an instance of Renderer
	*/
	public function testInstance()
	{
		$generator = new PHP;
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Renderer',
			$generator->getRenderer($this->configurator->stylesheet)
		);
	}

	/**
	* @testdox The returned instance contains its own source code in $renderer->source
	*/
	public function testInstanceSource()
	{
		$generator = new PHP;
		$renderer  = $generator->getRenderer($this->configurator->stylesheet);

		$this->assertObjectHasAttribute('source', $renderer, 'class');
		$this->assertStringStartsWith('class', $renderer->source);
	}

	/**
	* @testdox The name of the generated class can be passed to the constructor
	*/
	public function testClassNameConstructor()
	{
		$className = 'renderer_' . uniqid();
		$generator = new PHP($className);

		$this->assertInstanceOf(
			$className,
			$generator->getRenderer($this->configurator->stylesheet)
		);
	}

	/**
	* @testdox If no class name is set, a new random class name is generated for every call
	*/
	public function testClassNameRandom()
	{
		$generator = new PHP;
		$renderer1 = $generator->getRenderer($this->configurator->stylesheet);
		$renderer2 = $generator->getRenderer($this->configurator->stylesheet);

		$this->assertNotSame(
			get_class($renderer1),
			get_class($renderer2),
			'The two renderers should have different class names'
		);
	}

	/**
	* @testdox The class name can be set in $rendererGenerator->className
	*/
	public function testClassNameProp()
	{
		$className = 'renderer_' . uniqid();

		$generator = new PHP;
		$generator->className = $className;

		$this->assertInstanceOf(
			$className,
			$generator->getRenderer($this->configurator->stylesheet)
		);
	}

	/**
	* @testdox Ignores comments
	*/
	public function testComment()
	{
		$generator = new PHP;
		$xsl = 
			'<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
				<xsl:output method="html" encoding="utf-8" />
				<xsl:template match="FOO"><!-- Nothing here --></xsl:template>
			</xsl:stylesheet>';

		$this->assertNotContains(
			'Nothing',
			$generator->generate($xsl)
		);
	}

	/**
	* @testdox Throws an exception if a template contains a processing instruction
	* @expectedException RuntimeException
	*/
	public function testPI()
	{
		$generator = new PHP;
		$xsl = 
			'<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
				<xsl:output method="html" encoding="utf-8" />
				<xsl:template match="FOO"><?pi ?></xsl:template>
			</xsl:stylesheet>';

		$generator->generate($xsl);
	}

	/**
	* @testdox Throws an exception when encountering unsupported XSL elements
	* @expectedException RuntimeException
	* @expectedExceptionMessage Element 'xsl:foo' is not supported
	*/
	public function testUnsupported()
	{
		$generator = new PHP;
		$xsl = 
			'<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
				<xsl:output method="html" encoding="utf-8" />
				<xsl:template match="FOO"><xsl:foo/></xsl:template>
			</xsl:stylesheet>';

		$generator->generate($xsl);
	}

	/**
	* @testdox Throws an exception when encountering namespaced elements
	* @expectedException RuntimeException
	* @expectedExceptionMessage Namespaced element 'x:x' is not supported
	*/
	public function testUnsupportedNamespace()
	{
		$generator = new PHP;
		$xsl = 
			'<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
				<xsl:output method="html" encoding="utf-8" />
				<xsl:template match="FOO"><x:x xmlns:x="urn:x"/></xsl:template>
			</xsl:stylesheet>';

		$generator->generate($xsl);
	}

	/**
	* @testdox Throws an exception on <xsl:copy-of/> that does not copy an attribute
	* @expectedException RuntimeException
	* @expectedExceptionMessage Unsupported <xsl:copy-of/> expression 'current()'
	*/
	public function testUnsupportedCopyOf()
	{
		$generator = new PHP;
		$xsl = 
			'<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
				<xsl:output method="html" encoding="utf-8" />
				<xsl:template match="FOO"><xsl:copy-of select="current()"/></xsl:template>
			</xsl:stylesheet>';

		$generator->generate($xsl);
	}

	/**
	* @testdox Throws an exception on unterminated strings in XPath expressions
	* @expectedException RuntimeException
	* @expectedExceptionMessage Unterminated string literal
	*/
	public function testUnterminatedStrings()
	{
		$generator = new PHP;
		$xsl =
			'<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
				<xsl:output method="html" encoding="utf-8" />
				<xsl:template match="X"><xsl:value-of select="&quot;"/></xsl:template>
			</xsl:stylesheet>';

		$generator->generate($xsl);
	}

	/**
	* @testdox setParameter() accepts values that contain both types of quotes
	*/
	public function testSetParameterBothQuotes()
	{
		$configurator = new Configurator;
		$configurator->stylesheet->parameters->add('foo');
		$configurator->tags->add('X')->defaultTemplate = '<xsl:value-of select="$foo"/>';

		$renderer = $configurator->getRenderer('PHP');

		$values = [
			'"\'...\'"',
			'\'\'""...\'\'"\'"'
		];

		foreach ($values as $value)
		{
			$renderer->setParameter('foo', $value);
			$this->assertSame($value, $renderer->render('<rt><X/></rt>'));
		}
	}

	/**
	* @dataProvider getHTMLData
	* @testdox HTML rendering
	*/
	public function testHTML($xml, $xsl, $html)
	{
		$this->assertSame(
			$html,
			$this->getRendererFromXsl($xsl)->render($xml)
		);
	}

	/**
	* @dataProvider getXHTMLData
	* @testdox XHTML rendering
	*/
	public function testXHTML($xml, $xsl, $xhtml)
	{
		$this->assertSame(
			$xhtml,
			$this->getRendererFromXsl($xsl)->render($xml)
		);
	}

	public function getHTMLData()
	{
		return $this->getRendererData('e*', 'html');
	}

	public function getXHTMLData()
	{
		return $this->getRendererData('e*', 'xhtml');
	}

	public function getRendererData($pattern, $outputMethod)
	{
		$testCases = [];
		foreach (glob(__DIR__ . '/data/' . $pattern . '.xml') as $filepath)
		{
			$testCases[] = [
				file_get_contents($filepath),
				file_get_contents(substr($filepath, 0, -3) . $outputMethod . '.xsl'),
				file_get_contents(substr($filepath, 0, -3) . $outputMethod)
			];
		}

		return $testCases;
	}

	/**
	* @testdox BBCodes from repository.xml render nicely
	* @dataProvider getBBCodesData
	*/
	public function testBBCodes($xml, $xsl, $html)
	{
		$this->assertSame(
			$html,
			$this->getRendererFromXsl($xsl)->render($xml)
		);
	}

	public function getBBCodesData()
	{
		return $this->getRendererData('b*', 'html');
	}

	/**
	* @testdox Rendering tests from plugins
	* @dataProvider getPluginsData
	*/
	public function testPlugins($xml, $xsl, $html)
	{
		$this->assertSame(
			$html,
			$this->getRendererFromXsl($xsl)->render($xml)
		);
	}

	public function getPluginsData()
	{
		return $this->getRendererData('*.*', 'html');
	}

	/**
	* @testdox Edge cases
	* @dataProvider getEdgeCases
	*/
	public function testEdgeCases($xml, $configuratorSetup, $rendererSetup = null)
	{
		$configurator = new Configurator;
		call_user_func($configuratorSetup, $configurator);

		$phpRenderer  = $configurator->getRenderer('PHP');
		$xsltRenderer = $configurator->getRenderer('XSLT');

		if ($rendererSetup)
		{
			call_user_func($rendererSetup, $phpRenderer);
			call_user_func($rendererSetup, $xsltRenderer);
		}

		$this->assertSame(
			$xsltRenderer->render($xml),
			$phpRenderer->render($xml)
		);
	}

	public function getEdgeCases()
	{
		return [
			[
				"<rt>x <B/> y</rt>",
				function ($configurator)
				{
					$configurator->tags->add('B')->defaultTemplate
						= '<b><xsl:apply-templates/></b>';
				}
			],
			[
				"<rt>x <B/> y</rt>",
				function ($configurator)
				{
					$configurator->tags->add('B')->defaultTemplate = new UnsafeTemplate(
						'<xsl:element name="{translate(name(),\'B\',\'b\')}"><xsl:apply-templates/></xsl:element>'
					);
				}
			],
			[
				"<rt>x <HR/> y</rt>",
				function ($configurator)
				{
					$configurator->tags->add('HR')->defaultTemplate = new UnsafeTemplate(
						'<xsl:element name="{translate(name(),\'HR\',\'hr\')}" />'
					);
				}
			],
			[
				'<rt><X/></rt>',
				function ($configurator)
				{
					$configurator->stylesheet->parameters->add('foo', "'FOO'");
					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:value-of select="$foo"/>';
				}
			],
			[
				'<rt><X/><X/></rt>',
				function ($configurator)
				{
					$configurator->stylesheet->parameters->add('foo', "count(//X)");
					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:value-of select="$foo"/>';
				}
			],
			[
				'<rt><X/></rt>',
				function ($configurator)
				{
					$configurator->stylesheet->parameters->add('foo');
					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:value-of select="$foo"/>';
				}
			],
			[
				'<rt><X/></rt>',
				function ($configurator)
				{
					$configurator->stylesheet->parameters->add('foo');
					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:value-of select="$foo"/>';
				},
				function ($renderer)
				{
					$renderer->setParameter('foo', 15);
				}
			],
			[
				'<rt><X/></rt>',
				function ($configurator)
				{
					$configurator->stylesheet->parameters->add('foo');
					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:if test="$foo">!</xsl:if>';
				}
			],
			[
				'<rt><X/></rt>',
				function ($configurator)
				{
					$configurator->stylesheet->parameters->add('foo');
					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:if test="not($foo)">!</xsl:if>';
				}
			],
			[
				'<rt><X/></rt>',
				function ($configurator)
				{
					$configurator->stylesheet->parameters->add('foo');
					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:if test="$foo">!</xsl:if>';
				},
				function ($renderer)
				{
					$renderer->setParameter('foo', true);
				}
			],
			[
				'<rt><X/></rt>',
				function ($configurator)
				{
					$configurator->stylesheet->parameters->add('foo');
					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:if test="not($foo)">!</xsl:if>';
				},
				function ($renderer)
				{
					$renderer->setParameter('foo', true);
				}
			],
			[
				'<rt><X/></rt>',
				function ($configurator)
				{
					$configurator->stylesheet->parameters->add('foo', 3);
					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:if test="$foo &lt; 5">!</xsl:if>';
				}
			],
			[
				'<rt><X/></rt>',
				function ($configurator)
				{
					$configurator->stylesheet->parameters->add('xxx', 3);
					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:if test="$xxx &lt; 1">!</xsl:if>';
				}
			],
			[
				'<rt><X/><Y>1</Y><Y>2</Y></rt>',
				function ($configurator)
				{
					$configurator->stylesheet->parameters->add('xxx', '//Y');
					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:value-of select="$xxx"/>';
				}
			],
			[
				'<rt xmlns:html="urn:s9e:TextFormatter:html"><html:b>...</html:b></rt>',
				function ($configurator)
				{
					$configurator->tags->add('html:b')->defaultTemplate
						= '<b><xsl:apply-templates /></b>';
				}
			],
			[
				'<rt xmlns:x="urn:s9e:TextFormatter:x"><x:b>...</x:b><x:c>!!!</x:c></rt>',
				function ($configurator)
				{
					$configurator->tags->add('x:b')->defaultTemplate
						= '<b><xsl:apply-templates /></b>';

					$configurator->stylesheet->setWildcardTemplate(
						'x',
						'<span><xsl:apply-templates /></span>'
					);
				}
			],
			[
				'<rt><X/><X i="8"/><X i="4"/><X i="2"/></rt>',
				function ($configurator)
				{
					$tag = $configurator->tags->add('X');
					$tag->defaultTemplate = 'default';
					$tag->templates['@i < 5'] = '5';
					$tag->templates['@i < 3'] = '3';
				}
			],
			[
				'<rt><X/><X i="8"/><X i="4"/><X i="2"/></rt>',
				function ($configurator)
				{
					$tag = $configurator->tags->add('X');
					$tag->defaultTemplate = 'default';
					$tag->templates['@i < 3'] = '3';
					$tag->templates['@i < 5'] = '5';
				}
			],
			[
				'<rt xmlns:html="urn:s9e:TextFormatter:html"><html:b title="\'&quot;&amp;\'">...</html:b></rt>',
				function ($configurator)
				{
					$configurator->stylesheet->setWildcardTemplate(
						'html',
						new UnsafeTemplate('<xsl:element name="{local-name()}"><xsl:copy-of select="@*"/><xsl:apply-templates/></xsl:element>')
					);
				}
			],
			[
				'<rt><E>:)</E><E>:(</E></rt>',
				function ($configurator)
				{
					$configurator->tags->add('E')->defaultTemplate
						= '<xsl:choose><xsl:when test=".=\':)\'"><img src="happy.png" alt=":)"/></xsl:when><xsl:when test=".=\':(\'"><img src="sad.png" alt=":("/></xsl:when><xsl:otherwise><xsl:value-of select="."/></xsl:otherwise></xsl:choose>';
				}
			],
			[
				'<rt><E>:)</E><E>:(</E><E>:-)</E></rt>',
				function ($configurator)
				{
					$configurator->tags->add('E')->defaultTemplate
						= '<xsl:choose><xsl:when test=".=\':)\'or.=\':-)\'"><img src="happy.png" alt=":)"/></xsl:when><xsl:when test=".=\':(\'"><img src="sad.png" alt=":("/></xsl:when><xsl:otherwise><xsl:value-of select="."/></xsl:otherwise></xsl:choose>';
				}
			],
			[
				'<rt>x <X/> y</rt>',
				function ($configurator)
				{
					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:text>&amp;foo</xsl:text>';
				}
			],
			[
				'<rt>x <X>...</X> y</rt>',
				function ($configurator)
				{
					$configurator->tags->add('X')->defaultTemplate
						= '<b>x <i>i</i> <u>u</u> y</b>';
				}
			],
			[
				'<rt><X foo="FOO"/></rt>',
				function ($configurator)
				{
					$configurator->tags->add('X')->defaultTemplate
						= '<b><xsl:attribute name="title">
							<xsl:choose>
								<xsl:when test="@foo">foo=<xsl:value-of select="@foo"/>;</xsl:when>
								<xsl:otherwise>bar=<xsl:value-of select="@bar"/>;</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute></b>';
				}
			],
			[
				'<rt><X foo="FOO"/></rt>',
				function ($configurator)
				{
					$configurator->tags->add('X')->defaultTemplate
						= '<b><xsl:attribute name="title">
							<xsl:if test="@foo">foo=<xsl:value-of select="@foo"/>;</xsl:if>
							<xsl:if test="@bar">bar=<xsl:value-of select="@bar"/>;</xsl:if>
						</xsl:attribute></b>';
				}
			],
		];
	}

	protected function runCodeTest($xsl, $contains, $notContains)
	{
		if (strpos('xsl:output', $xsl) === false)
		{
			$xsl = '<xsl:output method="html" encoding="utf-8" />' . $xsl;
		}

		$xsl = '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">' . $xsl . '</xsl:stylesheet>';

		// Remove whitespace
		$dom = new DOMDocument;
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($xsl);
		$xsl = $dom->saveXML();

		$generator = new PHP('foo');
		$php = $generator->generate($xsl);

		if (isset($contains))
		{
			foreach ((array) $contains as $str)
			{
				$this->assertContains($str, $php);
			}
		}

		if (isset($notContains))
		{
			foreach ((array) $notContains as $str)
			{
				$this->assertNotContains($str, $php);
			}
		}
	}

	/**
	* @dataProvider getOptimizationTests
	* @testdox Code optimization tests
	*/
	public function testOptimizations($xsl, $contains = null, $notContains = null)
	{
		$this->runCodeTest($xsl, $contains, $notContains);
	}

	public function getOptimizationTests()
	{
		return [
			[
				'<xsl:template match="FOO"><br/></xsl:template>',
				"\$this->out.='<br>';"
			],
			[
				'<xsl:template match="FOO"><xsl:text/></xsl:template>',
				"if(\$nodeName==='FOO'){}",
				"\$this->out.='';"
			],
			[
				'<xsl:template match="FOO">
					<xsl:choose>
						<xsl:when test="@foo">foo</xsl:when>
						<xsl:otherwise><xsl:text/></xsl:otherwise>
					</xsl:choose>
				</xsl:template>',
				"{if(\$node->hasAttribute('foo')){\$this->out.='foo';}}",
				['else{}', "\$this->out.=''"]
			],
			[
				'<xsl:template match="html:*">
					<xsl:element name="{local-name()}">
						<xsl:copy-of select="@*"/>
						<xsl:apply-templates/>
					</xsl:element>
				</xsl:template>',
				[
					"=htmlspecialchars(\$this->xpath->evaluate('local-name()',\$node),2);\$this->out.='<'.\$",
					"\$this->out.='</'.\$e"
				],
				"\$this->out.='</'.htmlspecialchars("
			],
			[
				'<xsl:template match="FOO">...</xsl:template>',
				null,
				'foreach ($this->dynamicParams as $k => $v)'
			],
			[
				'<xsl:template match="FOO"><xsl:value-of select="\'foo\'"/></xsl:template>',
				"\$this->out.='foo';"
			],
			[
				'<xsl:template match="FOO"><xsl:value-of select="\'fo\\o\'"/></xsl:template>',
				"\$this->out.='fo\\\\o';"
			],
			[
				'<xsl:template match="FOO"><xsl:value-of select="\'&quot;&lt;AT&amp;T&gt;\'"/></xsl:template>',
				"\$this->out.='\"&lt;AT&amp;T&gt;';"
			],
			[
				'<xsl:template match="FOO"><xsl:value-of select="&quot;&apos;&lt;AT&amp;T&gt;&quot;"/></xsl:template>',
				"\$this->out.='\\'&lt;AT&amp;T&gt;';"
			],
			[
				'<xsl:template match="FOO"><b title="{\'&quot;foo&quot;\'}"></b></xsl:template>',
				var_export('<b title="&quot;foo&quot;"></b>', true)
			],
			[
				'<xsl:template match="FOO"><b title="{&quot;&apos;foo&apos;&quot;}"></b></xsl:template>',
				var_export('<b title="\'foo\'"></b>', true)
			],
		];
	}

	/**
	* @dataProvider getXPathTests
	* @testdox XPath expressions are inlined as PHP whenever possible
	*/
	public function testXPath($xsl, $contains = null, $notContains = null)
	{
		if (strpos('xsl:output', $xsl) === false)
		$this->runCodeTest($xsl, $contains, $notContains);
	}

	public function getXPathTests()
	{
		return [
			// XPath in values
			[
				'<xsl:template match="FOO"><xsl:value-of select="@bar"/></xsl:template>',
				"\$node->getAttribute('bar')"
			],
			[
				'<xsl:template match="FOO"><xsl:value-of select="."/></xsl:template>',
				"\$node->textContent"
			],
			[
				'<xsl:template match="FOO"><xsl:value-of select="$foo"/></xsl:template>',
				"\$this->params['foo']"
			],
			[
				'<xsl:template match="FOO"><xsl:value-of select="\'foo\'"/></xsl:template>',
				null,
				'$this->xpath->evaluate'
			],
			// XPath in conditions
			[
				'<xsl:template match="FOO"><xsl:if test="@foo">Foo</xsl:if></xsl:template>',
				"if(\$node->hasAttribute('foo'))"
			],
			[
				'<xsl:template match="FOO"><xsl:if test="not(@foo)">Foo</xsl:if></xsl:template>',
				"if(!\$node->hasAttribute('foo'))"
			],
			[
				'<xsl:template match="FOO"><xsl:if test="$foo">Foo</xsl:if></xsl:template>',
				"if(!empty(\$this->params['foo']))"
			],
			[
				'<xsl:template match="FOO"><xsl:if test="not($foo)">Foo</xsl:if></xsl:template>',
				"if(empty(\$this->params['foo']))"
			],
			[
				'<xsl:template match="FOO"><xsl:if test=".=\'foo\'">Foo</xsl:if></xsl:template>',
				"if(\$node->textContent==='foo')"
			],
			[
				'<xsl:template match="FOO"><xsl:if test=".=\'fo&quot;o\'">Foo</xsl:if></xsl:template>',
				"if(\$node->textContent==='fo\"o')"
			],
			[
				'<xsl:template match="FOO"><xsl:if test=".=\'&quot;_&quot;\'">Foo</xsl:if></xsl:template>',
				'if($node->textContent===\'"_"\')'
			],
			[
				'<xsl:template match="FOO"><xsl:if test=".=\'foo\'or.=\'bar\'">Foo</xsl:if></xsl:template>',
				"if(\$node->textContent==='foo'||\$node->textContent==='bar')"
			],
		];
	}
}