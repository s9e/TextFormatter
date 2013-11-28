<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators;

use DOMDocument;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Items\DynamicStylesheetParameter;
use s9e\TextFormatter\Configurator\Items\UnsafeTemplate;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP
*/
class PHPTest extends Test
{
	protected function tearDown()
	{
		array_map('unlink', glob(sys_get_temp_dir() . '/Renderer_*.php'));
	}

	protected function getRendererFromXsl($xsl)
	{
		$className = 'Renderer_' . md5($xsl);

		if (!class_exists($className, false))
		{
			$generator = new PHP;
			$generator->className = $className;

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
	* @testdox get() can be called multiple times with the same stylesheet
	*/
	public function testMultipleGet()
	{
		$generator = new PHP;
		$renderer1 = $generator->getRenderer($this->configurator->stylesheet);
		$renderer2 = $generator->getRenderer($this->configurator->stylesheet);

		$this->assertEquals($renderer1, $renderer2);
		$this->assertNotSame($renderer1, $renderer2);
	}

	/**
	* @testdox The returned instance contains its own source code in $renderer->source
	*/
	public function testInstanceSource()
	{
		$generator = new PHP;
		$renderer  = $generator->getRenderer($this->configurator->stylesheet);

		$this->assertObjectHasAttribute('source', $renderer);
		$this->assertContains('class Renderer', $renderer->source);
	}

	/**
	* @testdox If no class name is set, a class name is generated based on the renderer's source
	*/
	public function testClassNameGenerated()
	{
		$generator = new PHP;
		$xsl =
			'<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
				<xsl:output method="html" encoding="utf-8" indent="no"/>
				<xsl:template match="p"><p><xsl:apply-templates/></p></xsl:template>
				<xsl:template match="br"><br/></xsl:template>
				<xsl:template match="et|i|st"/>
			</xsl:stylesheet>';

		$this->assertContains(
			'class Renderer_b508f06eee8492e13c62ffc5c3c69011a6a769ff',
			$generator->generate($xsl)
		);
	}

	/**
	* @testdox The prefix used for generated class names can be changed in $rendererGenerator->defaultClassPrefix
	*/
	public function testClassNameGeneratedCustom()
	{
		$generator = new PHP;
		$generator->defaultClassPrefix = 'Foo\\Bar_renderer_';

		$xsl =
			'<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
				<xsl:output method="html" encoding="utf-8" indent="no"/>
				<xsl:template match="p"><p><xsl:apply-templates/></p></xsl:template>
				<xsl:template match="br"><br/></xsl:template>
				<xsl:template match="et|i|st"/>
			</xsl:stylesheet>';

		$this->assertContains(
			'class Bar_renderer_b508f06eee8492e13c62ffc5c3c69011a6a769ff',
			$generator->generate($xsl)
		);
	}

	/**
	* @testdox The class name can be set in $rendererGenerator->className
	*/
	public function testClassNameProp()
	{
		$className = uniqid('renderer_');

		$generator = new PHP;
		$generator->className = $className;

		$this->assertInstanceOf(
			$className,
			$generator->getRenderer($this->configurator->stylesheet)
		);
	}

	/**
	* @testdox The class name can be namespaced
	*/
	public function testNamespacedClass()
	{
		$className = uniqid('foo\\bar\\renderer_');

		$generator = new PHP;
		$generator->className = $className;

		$renderer  = $generator->getRenderer($this->configurator->stylesheet);

		$this->assertInstanceOf($className, $renderer);
		$this->assertContains("namespace foo\\bar;\n\nclass renderer_", $renderer->source);
	}

	/**
	* @testdox If $rendererGenerator->filepath is set, the renderer is saved to this file
	*/
	public function testFilepathProp()
	{
		$filepath  = $this->tempnam();

		$generator = new PHP;
		$generator->filepath = $filepath;

		$renderer  = $generator->getRenderer($this->configurator->stylesheet);

		$this->assertFileExists($filepath);
		$this->assertContains($renderer->source, file_get_contents($filepath));
	}

	/**
	* @testdox A path to a cache dir can be passed to the constructor
	*/
	public function testCacheDirConstructor()
	{
		$generator = new PHP('/tmp');

		$this->assertSame('/tmp', $generator->cacheDir);
	}

	/**
	* @testdox If $rendererGenerator->filepath is not set, and $rendererGenerator->cacheDir is set, the renderer is saved to the cache dir using the renderer's class name + '.php' as file name
	*/
	public function testCacheDirSave()
	{
		$cacheDir  = sys_get_temp_dir();
		$generator = new PHP($cacheDir);
		$renderer  = $generator->getRenderer($this->configurator->stylesheet);

		$this->assertFileExists(
			$cacheDir . '/Renderer_b508f06eee8492e13c62ffc5c3c69011a6a769ff.php'
		);
	}

	/**
	* @testdox When saving the renderer to the cache dir, backslashes in the class name are replaced with underscores
	*/
	public function testCacheDirSaveNamespace()
	{
		$cacheDir  = sys_get_temp_dir();
		$generator = new PHP($cacheDir);
		$generator->className = 'Foo\\Bar';
		$renderer  = $generator->getRenderer($this->configurator->stylesheet);

		$this->assertFileExists($cacheDir . '/Foo_Bar.php');
		unlink($cacheDir . '/Foo_Bar.php');
	}

	/**
	* @testdox If $rendererGenerator->filepath and $rendererGenerator->cacheDir are set, the renderer is saved to $rendererGenerator->filepath
	*/
	public function testCacheDirFilepath()
	{
		$cacheDir  = sys_get_temp_dir();
		$filepath  = $this->tempnam();

		$generator = new PHP($cacheDir);
		$generator->filepath = $filepath;
		$renderer  = $generator->getRenderer($this->configurator->stylesheet);

		$this->assertFileExists($filepath);
		$this->assertFileNotExists(
			$cacheDir . '/Renderer_b508f06eee8492e13c62ffc5c3c69011a6a769ff.php'
		);
	}

	/**
	* @testdox The name of the class of the last generated renderer is available in $rendererGenerator->lastClassName
	*/
	public function testLastClassName()
	{
		$generator = new PHP;
		$renderer  = $generator->getRenderer($this->configurator->stylesheet);

		$this->assertSame(get_class($renderer), $generator->lastClassName);
	}

	/**
	* @testdox The name of the class of the last saved renderer is available in $rendererGenerator->lastFilepath
	*/
	public function testLastFilepath()
	{
		$cacheDir  = sys_get_temp_dir();
		$generator = new PHP($cacheDir);
		$renderer  = $generator->getRenderer($this->configurator->stylesheet);

		$this->assertSame(
			$cacheDir . '/Renderer_b508f06eee8492e13c62ffc5c3c69011a6a769ff.php',
			$generator->lastFilepath
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
	* @testdox Correctly handles dynamic stylesheet parameters
	*/
	public function testDynamicParameters()
	{
		$configurator = new Configurator;
		$configurator->stylesheet->parameters['foo'] = new DynamicStylesheetParameter('count(//X)');
		$configurator->tags->add('X')->defaultTemplate = '<xsl:value-of select="$foo"/>';

		$renderer = $configurator->getRenderer('PHP');

		$this->assertSame('1',  $renderer->render('<rt><X/></rt>'));
		$this->assertSame('22', $renderer->render('<rt><X/><X/></rt>'));
	}

	/**
	* @testdox Elements found to be empty at runtime use the empty-elements tag syntax in XML mode by default
	*/
	public function testForceEmptyElementsTrue()
	{
		$configurator = new Configurator;
		$configurator->tags->add('X')->defaultTemplate = '<div><xsl:apply-templates/></div>';
		$configurator->stylesheet->setOutputMethod('xml');

		$configurator->setRendererGenerator('PHP');
		$renderer = $configurator->getRenderer();

		$this->assertSame('<div/>', $renderer->render('<rt><X/></rt>'));
	}

	/**
	* @testdox Elements found to be empty at runtime are not minimized if forceEmptyElements is FALSE
	*/
	public function testForceEmptyElementsFalse()
	{
		$configurator = new Configurator;
		$configurator->tags->add('X')->defaultTemplate = '<div><xsl:apply-templates/></div>';
		$configurator->stylesheet->setOutputMethod('xml');

		$configurator->setRendererGenerator('PHP')->forceEmptyElements = false;
		$renderer = $configurator->getRenderer();

		$this->assertSame('<div></div>', $renderer->render('<rt><X/></rt>'));
	}

	/**
	* @testdox Elements found to be empty at runtime are not minimized if useEmptyElements is FALSE
	*/
	public function testForceEmptyElementsTrueUseEmptyElementsFalse()
	{
		$configurator = new Configurator;
		$configurator->tags->add('X')->defaultTemplate = '<div><xsl:apply-templates/></div>';
		$configurator->stylesheet->setOutputMethod('xml');

		$generator = $configurator->setRendererGenerator('PHP');
		$generator->forceEmptyElements = true;
		$generator->useEmptyElements   = false;

		$renderer = $configurator->getRenderer();

		$this->assertSame('<div></div>', $renderer->render('<rt><X/></rt>'));
	}

	/**
	* @testdox Empty elements use the empty-elements tag syntax in XML mode by default
	*/
	public function testUseEmptyElementsTrue()
	{
		$configurator = new Configurator;
		$configurator->tags->add('X')->defaultTemplate = '<div></div>';
		$configurator->stylesheet->setOutputMethod('xml');

		$configurator->setRendererGenerator('PHP');
		$renderer = $configurator->getRenderer();

		$this->assertSame('<div/>', $renderer->render('<rt><X/></rt>'));
	}

	/**
	* @testdox Empty elements do not use the empty-elements tag syntax in XML mode if useEmptyElements is FALSE
	*/
	public function testUseEmptyElementsFalse()
	{
		$configurator = new Configurator;
		$configurator->tags->add('X')->defaultTemplate = '<div></div>';
		$configurator->stylesheet->setOutputMethod('xml');

		$configurator->setRendererGenerator('PHP')->useEmptyElements = false;
		$renderer = $configurator->getRenderer();

		$this->assertSame('<div></div>', $renderer->render('<rt><X/></rt>'));
	}

	/**
	* @testdox Empty void elements use the empty-elements tag syntax in XML mode even if useEmptyElements is FALSE
	*/
	public function testUseEmptyElementsFalseVoidTrue()
	{
		$configurator = new Configurator;
		$configurator->tags->add('X')->defaultTemplate = '<hr></hr>';
		$configurator->stylesheet->setOutputMethod('xml');

		$configurator->setRendererGenerator('PHP')->useEmptyElements = false;
		$renderer = $configurator->getRenderer();

		$this->assertSame('<hr/>', $renderer->render('<rt><X/></rt>'));
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
		return $this->getRendererData('EdgeCases/e*', 'html');
	}

	public function getXHTMLData()
	{
		return $this->getRendererData('EdgeCases/e*', 'xhtml');
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
	* @dataProvider getVoidTestsHTML
	* @testdox Rendering of void and empty elements in HTML
	*/
	public function testVoidHTML($xml, $xsl, $expected)
	{
		$this->assertSame(
			$expected,
			$this->getRendererFromXsl($xsl)->render($xml)
		);
	}

	public function getVoidTestsHTML()
	{
		return $this->getRendererData('VoidTests/*', 'html');
	}

	/**
	* @dataProvider getVoidTestsXHTML
	* @testdox Rendering of void and empty elements in XHTML
	*/
	public function testVoidXHTML($xml, $xsl, $expected)
	{
		$this->assertSame(
			$expected,
			$this->getRendererFromXsl($xsl)->render($xml)
		);
	}

	public function getVoidTestsXHTML()
	{
		return $this->getRendererData('VoidTests/*', 'xhtml');
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
		return $this->getRendererData('Plugins/*/*', 'html');
	}

	/**
	* @requires extension xsl
	* @testdox Matches the reference rendering in edge cases
	* @dataProvider getEdgeCases
	*/
	public function testEdgeCases($xml, $configuratorSetup, $rendererSetup = null)
	{
		$configurator = new Configurator;
		call_user_func($configuratorSetup, $configurator, $this);

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
					$configurator->tags->add('X')->defaultTemplate =
						'<b><xsl:attribute name="title">
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
					$configurator->tags->add('X')->defaultTemplate =
						'<b><xsl:attribute name="title">
							<xsl:if test="@foo">foo=<xsl:value-of select="@foo"/>;</xsl:if>
							<xsl:if test="@bar">bar=<xsl:value-of select="@bar"/>;</xsl:if>
						</xsl:attribute></b>';
				}
			],
			[
				'<rt><X foo="FOO"/></rt>',
				function ($configurator)
				{
					$configurator->tags->add('X')->defaultTemplate =
						'<xsl:choose>
							<xsl:when test="contains(.,\'a\')">
								<xsl:choose>
									<xsl:when test=".=1">aaa</xsl:when>
									<xsl:otherwise>bbb</xsl:otherwise>
								</xsl:choose>
							</xsl:when>
							<xsl:otherwise>
								<xsl:choose>
									<xsl:when test=".=1">xxx</xsl:when>
									<xsl:otherwise>yyy</xsl:otherwise>
								</xsl:choose>
							</xsl:otherwise>
						</xsl:choose>';
				}
			],
			[
				'<rt><X foo="FOO">..</X></rt>',
				function ($configurator, $test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}

					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:value-of select="string-length(@foo)"/>';
				}
			],
			[
				'<rt><X foo="FOO">..</X></rt>',
				function ($configurator, $test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}

					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:value-of select="string-length()"/>';
				}
			],
			[
				'<rt><X foo="FOO">..</X></rt>',
				function ($configurator, $test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}

					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:value-of select="string-length(@bar)"/>';
				}
			],
			[
				'<rt><X foo="ABCDEF0153">..</X></rt>',
				function ($configurator, $test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}

					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:value-of select="substring(@foo,2,3)"/>';
				}
			],
			[
				'<rt><X foo="ABCDEF0153">..</X></rt>',
				function ($configurator, $test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}

					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:value-of select="substring(@foo,0,3)"/>';
				}
			],
			[
				'<rt><X foo="ABCDEF0153">..</X></rt>',
				function ($configurator, $test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}

					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:value-of select="substring(@foo,2,-3)"/>';
				}
			],
			[
				'<rt><X foo="ABCDEF0153" x="3">..</X></rt>',
				function ($configurator, $test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}

					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:value-of select="substring(@foo,@x)"/>';
				}
			],
			[
				'<rt><X foo="ABCDEF0153" x="3">..</X></rt>',
				function ($configurator, $test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}

					$configurator->tags->add('X')->defaultTemplate
						= '<xsl:value-of select="substring(@foo,3,@x)"/>';
				}
			],
		];
	}

	protected function runCodeTest($xsl, $contains, $notContains, $setup = null)
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

		$generator = new PHP;
		$generator->className = 'foo';

		if (isset($setup))
		{
			call_user_func($setup, $generator, $this);
		}

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
	* @requires extension tokenizer
	* @dataProvider getXPathTests
	* @testdox XPath expressions are inlined as PHP whenever possible
	*/
	public function testXPath($xsl, $contains = null, $notContains = null, $setup = null)
	{
		if (isset($setup))
		{
			call_user_func($setup, $this);
		}

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
			[
				'<xsl:template match="*"><xsl:value-of select="local-name()"/></xsl:template>',
				'$node->localName',
				'$this->xpath->evaluate'
			],
			[
				'<xsl:template match="*"><xsl:value-of select="name()"/></xsl:template>',
				'$node->nodeName',
				'$this->xpath->evaluate'
			],
			[
				'<xsl:template match="*"><xsl:value-of select="\'foo\'"/></xsl:template>',
				"\$this->out.='foo';"
			],
			[
				'<xsl:template match="*"><xsl:value-of select=\'"foo"\'/></xsl:template>',
				"\$this->out.='foo';"
			],
			[
				'<xsl:template match="*"><xsl:value-of select="123"/></xsl:template>',
				"\$this->out.='123';"
			],
			[
				'<xsl:template match="*"><xsl:value-of select="not(@bar)"/></xsl:template>',
				"\$this->xpath->evaluate('not(@bar)',\$node)"
			],
			[
				'<xsl:template match="X"><xsl:value-of select="string-length(@bar)"/></xsl:template>',
				"mb_strlen(\$node->getAttribute('bar'),'utf-8')",
				'string-length',
				function ($test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}
				}
			],
			[
				'<xsl:template match="X"><xsl:value-of select="string-length()"/></xsl:template>',
				"mb_strlen(\$node->textContent,'utf-8')",
				'string-length',
				function ($test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}
				}
			],
			[
				'<xsl:template match="X"><xsl:value-of select="substring(.,1,2)"/></xsl:template>',
				"mb_substr(\$node->textContent,0,2,'utf-8')",
				'substring',
				function ($test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}
				}
			],
			[
				// NOTE: as per XPath specs, the length is adjusted to the negative position
				'<xsl:template match="X"><xsl:value-of select="substring(.,0,2)"/></xsl:template>',
				"mb_substr(\$node->textContent,0,1,'utf-8')",
				'substring',
				function ($test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}
				}
			],
			[
				'<xsl:template match="X"><xsl:value-of select="substring(.,@x,1)"/></xsl:template>',
				"mb_substr(\$node->textContent,max(0,\$node->getAttribute('x')-1),1,'utf-8')",
				'substring',
				function ($test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}
				}
			],
			[
				'<xsl:template match="X"><xsl:value-of select="substring(.,1,@x)"/></xsl:template>',
				"mb_substr(\$node->textContent,0,max(0,\$node->getAttribute('x')),'utf-8')",
				'substring',
				function ($test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}
				}
			],
			[
				'<xsl:template match="X"><xsl:value-of select="substring(.,2)"/></xsl:template>',
				"mb_substr(\$node->textContent,1,null,'utf-8')",
				'substring',
				function ($test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}
				}
			],
			[
				'<xsl:template match="X"><xsl:value-of select="translate(@bar,&quot;abc&quot;,&quot;ABC&quot;)"/></xsl:template>',
				"strtr(\$node->getAttribute('bar'),'abc','ABC')"
			],
			[
				'<xsl:template match="X"><xsl:value-of select="translate(@bar,\'abc\',\'ABC\')"/></xsl:template>',
				"strtr(\$node->getAttribute('bar'),'abc','ABC')"
			],
			[
				'<xsl:template match="X"><xsl:value-of select="translate(@bar,\'éè\',\'ÉÈ\')"/></xsl:template>',
				"strtr(\$node->getAttribute('bar'),['é'=>'É','è'=>'È'])"
			],
			[
				'<xsl:template match="X"><xsl:value-of select="translate(@bar,\'ab\',\'ABC\')"/></xsl:template>',
				"strtr(\$node->getAttribute('bar'),'ab','AB')"
			],
			[
				'<xsl:template match="X"><xsl:value-of select="translate(@bar,\'abcd\',\'AB\')"/></xsl:template>',
				"strtr(\$node->getAttribute('bar'),['a'=>'A','b'=>'B','c'=>'','d'=>''])"
			],
			[
				'<xsl:template match="X"><xsl:value-of select="translate(@bar,\'abbd\',\'ABCD\')"/></xsl:template>',
				"strtr(\$node->getAttribute('bar'),'abd','ABD')"
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
				'<xsl:template match="FOO"><xsl:if test="@foo=\'foo\'">Foo</xsl:if></xsl:template>',
				"if(\$node->getAttribute('foo')==='foo')"
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
			[
				'<xsl:template match="FOO"><xsl:if test=".=3">Foo</xsl:if></xsl:template>',
				"if(\$node->textContent==3)"
			],
			[
				'<xsl:template match="FOO"><xsl:if test=".=022">Foo</xsl:if></xsl:template>',
				"if(\$node->textContent==22)"
			],
			[
				'<xsl:template match="FOO"><xsl:if test="044=.">Foo</xsl:if></xsl:template>',
				"if(44==\$node->textContent)"
			],
			[
				'<xsl:template match="FOO"><xsl:if test="@foo != @bar">Foo</xsl:if></xsl:template>',
				"if(\$node->getAttribute('foo')!==\$node->getAttribute('bar'))"
			],
			[
				'<xsl:template match="FOO"><xsl:if test="@foo = @bar or @baz">Foo</xsl:if></xsl:template>',
				"if(\$node->getAttribute('foo')===\$node->getAttribute('bar')||\$node->hasAttribute('baz'))"
			],
			[
				'<xsl:template match="FOO"><xsl:if test="not(@foo) and @bar">Foo</xsl:if></xsl:template>',
				"if(!\$node->hasAttribute('foo')&&\$node->hasAttribute('bar'))"
			],
			[
				'<xsl:template match="FOO"><xsl:if test=".=\'x\'or.=\'y\'or.=\'z\'">Foo</xsl:if></xsl:template>',
				"if(\$node->textContent==='x'||\$node->textContent==='y'||\$node->textContent==='z')"
			],
			[
				'<xsl:template match="FOO"><xsl:if test="contains(@foo,\'x\')">Foo</xsl:if></xsl:template>',
				"(strpos(\$node->getAttribute('foo'),'x')!==false)"
			],
			[
				'<xsl:template match="FOO"><xsl:if test=" contains( @foo , \'x\' ) ">Foo</xsl:if></xsl:template>',
				"(strpos(\$node->getAttribute('foo'),'x')!==false)"
			],
			[
				'<xsl:template match="FOO"><xsl:if test="@foo and (@bar or @baz)">...</xsl:if></xsl:template>',
				"\$node->hasAttribute('foo')&&(\$node->hasAttribute('bar')||\$node->hasAttribute('baz'))",
				null,
				function ()
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						$this->markTestSkipped();
					}
				}
			],
			[
				'<xsl:template match="FOO"><xsl:if test="(@a = @b) or (@b = @c)">...</xsl:if></xsl:template>',
				"(\$node->getAttribute('a')===\$node->getAttribute('b'))||(\$node->getAttribute('b')===\$node->getAttribute('c'))",
				null,
				function ()
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						$this->markTestSkipped();
					}
				}
			],
			// Custom representations
			[
				'<xsl:template match="FOO"><xsl:if test="contains(\'upperlowerdecim\',substring(@type,1,5))">Foo</xsl:if></xsl:template>',
				"if(strpos('upperlowerdecim',substr(\$node->getAttribute('type'),0,5))!==false)"
			],
			[
				'<xsl:template match="FOO"><xsl:value-of select="substring(\'songWw\',6-5*boolean(@songid),5)"/></xsl:template>',
				"(\$node->hasAttribute('songid')?'songW':'w')"
			],
			[
				'<xsl:template match="FOO"><hr title="{250-210*boolean(@songid)}"/></xsl:template>',
				"(\$node->hasAttribute('songid')?40:250)"
			],
			[
				'<xsl:template match="FOO"><xsl:value-of select="substring(\'archl\',5-4*boolean(@archive_id|@chapter_id),4)"/></xsl:template>',
				"(\$node->hasAttribute('archive_id')||\$node->hasAttribute('chapter_id')?'arch':'l')"
			],
			[
				'<xsl:template match="FOO"><iframe height="{120-78*boolean(@track_id|@track_num)}"/></xsl:template>',
				"(\$node->hasAttribute('track_id')||\$node->hasAttribute('track_num')?42:120)",
				'@track_'
			],
			[
				"<xsl:template match='FOO'><hr title=\"{380-300*(contains(@uri,':track:')orcontains(@path,'/track/'))}\"/></xsl:template>",
				"(strpos(\$node->getAttribute('uri'),':track:')!==false||strpos(\$node->getAttribute('path'),'/track/')!==false?80:380)"
			],
		];
	}

	/**
	* @requires extension mbstring
	* @testdox useMultibyteStringFunctions is set to TRUE if mbstring is available
	*/
	public function testMbstringSet()
	{
		$generator = new PHP;
		$this->assertTrue($generator->useMultibyteStringFunctions);
	}

	/**
	* @testdox mbstring functions are not used if $useMultibyteStringFunctions is FALSE
	*/
	public function testNoMbstring()
	{
		$this->runCodeTest(
			'<xsl:template match="X"><xsl:value-of select="string-length(@foo)"/></xsl:template>',
			'string-length',
			'mb_strlen',
			function ($generator)
			{
				$generator->useMultibyteStringFunctions = false;
			}
		);
	}

	/**
	* @requires extension tokenizer
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
					<xsl:element name="{concat(\'x\',local-name())}">
						<xsl:copy-of select="@*"/>
						<xsl:apply-templates/>
					</xsl:element>
				</xsl:template>',
				[
					"\$this->out.='<'.\$e",
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
			[
				'<xsl:template match="*"><xsl:value-of select="local-name()"/></xsl:template>',
				'$this->out.=$node->localName',
				'htmlspecialchars($node->localName'
			],
			[
				'<xsl:template match="*"><xsl:value-of select="name()"/></xsl:template>',
				'$this->out.=$node->nodeName',
				'htmlspecialchars($node->nodeName'
			],
			[
				// This test ensures that we concatenate inside the htmlspecialchars() call rather
				// than concatenate the result of two htmlspecialchars() calls
				'<xsl:template match="*"><xsl:value-of select="@foo"/><xsl:value-of select="@bar"/></xsl:template>',
				"htmlspecialchars(\$node->getAttribute('foo').\$node->getAttribute('bar')"
			],
			[
				// This test ensures that we pre-escape literals before merging htmlspecialchars()
				// calls together
				'<xsl:template match="FOO"><img src="{$PATH}/bar.png"/></xsl:template>',
				"'<img src=\"'.htmlspecialchars(\$this->params['PATH'],2).'/bar.png\">'"
			],
			[
				'<xsl:template match="FOO[@bar]|BAR[@baz]">...</xsl:template>
				<xsl:template match="BAZ[@quux]">***</xsl:template>',
				[
					"if(\$nodeName==='BAZ'",
					"if((\$nodeName==='BAR'"
				],
				[
					"if((\$nodeName==='BAZ'",
					"if(\$nodeName==='BAR'"
				]
			],
			[
				// Not part of optimizeCode() but considered an optimization nonetheless
				'<xsl:template match="FOO">Hi</xsl:template>',
				null,
				'getParamAsXPath'
			],
			[
				// Not part of optimizeCode() but considered an optimization nonetheless
				'<xsl:template match="FOO"><xsl:apply-templates/></xsl:template>',
				null,
				'$this->xpath'
			],
			[
				'<xsl:template match="FOO"><xsl:apply-templates select="*"/></xsl:template>',
				'$this->xpath = new \\DOMXPath'
			],
		];
	}

	/**
	* @requires extension tokenizer
	* @testdox optimizeConcatenations() does not merge incompatible htmlspecialchars() calls
	*/
	public function testOptimizeConcatenationsNoIncompatibleMerge()
	{
		$php = 'if($node->hasAttribute(\'foo\'){$this->out.='
		     . 'htmlspecialchars($node->textContent,1).'
		     . 'htmlspecialchars($node->textContent,2);}';

		$generator = new DummyPHPRendererGenerator;
		$generator->php = $php;
		$generator->optimizeCode();

		$this->assertSame($php, $generator->php);
	}
}

class DummyPHPRendererGenerator extends PHP
{
	public $php;

	public function optimizeCode()
	{
		parent::optimizeCode();
	}
}