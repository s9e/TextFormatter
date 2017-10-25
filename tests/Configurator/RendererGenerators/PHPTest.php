<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators;

use DOMDocument;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Items\DynamicTemplateParameter;
use s9e\TextFormatter\Configurator\Items\UnsafeTemplate;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP;
use s9e\TextFormatter\Tests\Plugins\BBCodes\BBCodesTest;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\Serializer
*/
class PHPTest extends Test
{
	protected function setUp()
	{
		$this->configurator->rendering->engine = 'PHP';
	}

	protected function tearDown()
	{
		array_map('unlink', glob(sys_get_temp_dir() . '/*enderer_*.php'));
	}

	protected function getRendererSource()
	{
		if (empty($this->configurator->rendering->engine->lastFilepath))
		{
			$this->configurator->rendering->getRenderer();
		}

		return file_get_contents($this->configurator->rendering->engine->lastFilepath);
	}

	/**
	* @testdox Returns an instance of Renderer
	*/
	public function testInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Renderer',
			$this->configurator->rendering->getRenderer()
		);
	}

	/**
	* @testdox getRenderer() can be called multiple times with the same rendering configuration
	*/
	public function testMultipleGetRenderer()
	{
		$renderer1 = $this->configurator->rendering->getRenderer();
		$renderer2 = $this->configurator->rendering->getRenderer();

		$this->assertEquals($renderer1, $renderer2);
		$this->assertNotSame($renderer1, $renderer2);
	}

	/**
	* @testdox If no class name is set, a class name is generated based on the renderer's source
	*/
	public function testClassNameGenerated()
	{
		$this->assertRegexp(
			'/class Renderer_\\w{40}/',
			$this->getRendererSource()
		);
	}

	/**
	* @testdox The prefix used for generated class names can be changed in $rendererGenerator->defaultClassPrefix
	*/
	public function testClassNameGeneratedCustom()
	{
		$this->configurator->rendering->engine->defaultClassPrefix = 'Foo\\Bar_renderer_';

		$this->assertRegexp(
			'/class Bar_renderer_\\w{40}/',
			$this->getRendererSource()
		);
	}

	/**
	* @testdox The class name can be set in $rendererGenerator->className
	*/
	public function testClassNameProp()
	{
		$className = uniqid('renderer_');
		$this->configurator->rendering->engine->className = $className;

		$this->assertInstanceOf(
			$className,
			$this->configurator->rendering->getRenderer()
		);
	}

	/**
	* @testdox The class name can be namespaced
	*/
	public function testNamespacedClass()
	{
		$className = uniqid('foo\\bar\\renderer_');
		$this->configurator->rendering->engine->className = $className;

		$renderer = $this->configurator->rendering->getRenderer();

		$this->assertInstanceOf($className, $renderer);
		$this->assertContains("namespace foo\\bar;\n\nclass renderer_", $this->getRendererSource());
	}

	/**
	* @testdox If $rendererGenerator->filepath is set, the renderer is saved to this file
	*/
	public function testFilepathProp()
	{
		$filepath = $this->tempnam();
		$this->configurator->rendering->engine->filepath = $filepath;
		$this->configurator->rendering->getRenderer();

		$this->assertFileExists($filepath);
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
	* @testdox Uses the system's temporary files dir if no cache dir is passed to the constructor
	*/
	public function testSysCacheDir()
	{
		$generator = new PHP;

		$this->assertSame(sys_get_temp_dir(), $generator->cacheDir);
	}

	/**
	* @testdox If $rendererGenerator->filepath is not set, and $rendererGenerator->cacheDir is set, the renderer is saved to the cache dir using the renderer's class name + '.php' as file name
	*/
	public function testCacheDirSave()
	{
		$cacheDir = sys_get_temp_dir();
		$this->configurator->rendering->engine->cacheDir = $cacheDir;
		$renderer = $this->configurator->rendering->getRenderer();
		$filepath = $cacheDir . '/' . get_class($renderer) . '.php';

		$this->assertFileExists($filepath);
		unlink($filepath);
	}

	/**
	* @testdox When saving the renderer to the cache dir, backslashes in the class name are replaced with underscores
	*/
	public function testCacheDirSaveNamespace()
	{
		$cacheDir = sys_get_temp_dir();
		$this->configurator->rendering->engine->cacheDir  = $cacheDir;
		$this->configurator->rendering->engine->className = 'Foo\\Bar';
		$this->configurator->rendering->getRenderer();

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

		$this->configurator->rendering->engine->cacheDir = $cacheDir;
		$this->configurator->rendering->engine->filepath = $filepath;

		$renderer = $this->configurator->rendering->getRenderer();

		$this->assertFileExists($filepath);
		$this->assertFileNotExists($cacheDir . '/' . get_class($renderer) . '.php');
	}

	/**
	* @testdox The name of the class of the last generated renderer is available in $rendererGenerator->lastClassName
	*/
	public function testLastClassName()
	{
		$this->assertSame(
			get_class($this->configurator->rendering->getRenderer()),
			$this->configurator->rendering->engine->lastClassName
		);
	}

	/**
	* @testdox The name of the file of the last saved renderer is available in $rendererGenerator->lastFilepath
	*/
	public function testLastFilepath()
	{
		$cacheDir = sys_get_temp_dir();
		$this->configurator->rendering->engine->cacheDir  = $cacheDir;

		$this->configurator->rendering->getRenderer();

		$this->assertRegexp(
			'(^' . preg_quote($cacheDir) . '/Renderer_\\w{40}\\.php$)',
			$this->configurator->rendering->engine->lastFilepath
		);
	}

	/**
	* @testdox Ignores comments
	*/
	public function testComment()
	{
		$this->configurator->tags->add('X')->template = '<!-- Nothing here -->';

		$this->assertNotContains(
			'Nothing',
			$this->getRendererSource()
		);
	}

	/**
	* @testdox Throws an exception if a template contains a processing instruction
	* @expectedException RuntimeException
	*/
	public function testPI()
	{
		$this->configurator->tags->add('X')->template = '<?pi ?>';
		$this->configurator->rendering->getRenderer();
	}

	/**
	* @testdox Throws an exception when encountering unsupported XSL elements
	* @expectedException RuntimeException
	* @expectedExceptionMessage Element 'xsl:foo' is not supported
	*/
	public function testUnsupported()
	{
		$this->configurator->tags->add('X')->template = '<xsl:foo/>';
		$this->configurator->rendering->getRenderer();
	}

	/**
	* @testdox Throws an exception on <xsl:copy-of/> that does not copy an attribute
	* @expectedException RuntimeException
	* @expectedExceptionMessage Unsupported <xsl:copy-of/> expression 'current()'
	*/
	public function testUnsupportedCopyOf()
	{
		$this->configurator->tags->add('X')->template = '<xsl:copy-of select="current()"/>';
		$this->configurator->rendering->getRenderer();
	}

	/**
	* @requires extension xsl
	* @testdox Matches the reference rendering in edge cases
	* @dataProvider getEdgeCases
	*/
	public function testEdgeCases($xml, $configuratorSetup, $rendererSetup = null)
	{
		$this->runTestEdgeCase($xml, $configuratorSetup, $rendererSetup, false);
	}

	/**
	* @requires extension xsl
	* @testdox Matches the reference rendering in edge cases (quick renderer)
	* @dataProvider getEdgeCases
	*/
	public function testEdgeCasesQuick($xml, $configuratorSetup, $rendererSetup = null)
	{
		$this->runTestEdgeCase($xml, $configuratorSetup, $rendererSetup, true);
	}

	protected function runTestEdgeCase($xml, $configuratorSetup, $rendererSetup, $enableQuickRenderer)
	{
		call_user_func($configuratorSetup, $this->configurator, $this);

		$this->configurator->rendering->engine = 'XSLT';
		$xsltRenderer = $this->configurator->rendering->getRenderer();

		$this->configurator->rendering->engine = 'PHP';
		$this->configurator->rendering->engine->enableQuickRenderer = $enableQuickRenderer;
		$phpRenderer  = $this->configurator->rendering->getRenderer();

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
				"<r>x <B/> y</r>",
				function ($configurator)
				{
					$configurator->tags->add('B')->template
						= '<b><xsl:apply-templates/></b>';
				}
			],
			[
				"<r>x <B/> y</r>",
				function ($configurator)
				{
					$configurator->tags->add('B')->template = new UnsafeTemplate(
						'<xsl:element name="{translate(name(),\'B\',\'b\')}"><xsl:apply-templates/></xsl:element>'
					);
				}
			],
			[
				"<r>x <HR/> y</r>",
				function ($configurator)
				{
					$configurator->tags->add('HR')->template = new UnsafeTemplate(
						'<xsl:element name="{translate(name(),\'HR\',\'hr\')}" />'
					);
				}
			],
			[
				'<r><X/></r>',
				function ($configurator)
				{
					$configurator->rendering->parameters->add('foo', "'FOO'");
					$configurator->tags->add('X')->template
						= '<xsl:value-of select="$foo"/>';
				}
			],
			[
				'<r><X/><X/></r>',
				function ($configurator)
				{
					$configurator->rendering->parameters->add('foo', "count(//X)");
					$configurator->tags->add('X')->template
						= '<xsl:value-of select="$foo"/>';
				}
			],
			[
				'<r><X/></r>',
				function ($configurator)
				{
					$configurator->rendering->parameters->add('foo');
					$configurator->tags->add('X')->template
						= '<xsl:value-of select="$foo"/>';
				}
			],
			[
				'<r><X/></r>',
				function ($configurator)
				{
					$configurator->rendering->parameters->add('foo');
					$configurator->tags->add('X')->template
						= '<xsl:value-of select="$foo"/>';
				},
				function ($renderer)
				{
					$renderer->setParameter('foo', 15);
				}
			],
			[
				'<r><X/></r>',
				function ($configurator)
				{
					$configurator->rendering->parameters->add('foo');
					$configurator->tags->add('X')->template
						= '<xsl:if test="$foo">!</xsl:if>';
				}
			],
			[
				'<r><X/></r>',
				function ($configurator)
				{
					$configurator->rendering->parameters->add('foo');
					$configurator->tags->add('X')->template
						= '<xsl:if test="not($foo)">!</xsl:if>';
				}
			],
			[
				'<r><X/></r>',
				function ($configurator)
				{
					$configurator->rendering->parameters->add('foo');
					$configurator->tags->add('X')->template
						= '<xsl:if test="$foo">!</xsl:if>';
				},
				function ($renderer)
				{
					$renderer->setParameter('foo', true);
				}
			],
			[
				'<r><X/></r>',
				function ($configurator)
				{
					$configurator->rendering->parameters->add('foo');
					$configurator->tags->add('X')->template
						= '<xsl:if test="not($foo)">!</xsl:if>';
				},
				function ($renderer)
				{
					$renderer->setParameter('foo', true);
				}
			],
			[
				'<r><X/></r>',
				function ($configurator)
				{
					$configurator->rendering->parameters->add('foo', 3);
					$configurator->tags->add('X')->template
						= '<xsl:if test="$foo &lt; 5">!</xsl:if>';
				}
			],
			[
				'<r><X/></r>',
				function ($configurator)
				{
					$configurator->rendering->parameters->add('xxx', 3);
					$configurator->tags->add('X')->template
						= '<xsl:if test="$xxx &lt; 1">!</xsl:if>';
				}
			],
			[
				'<r><X/><Y>1</Y><Y>2</Y></r>',
				function ($configurator)
				{
					$configurator->rendering->parameters->add('xxx', '//Y');
					$configurator->tags->add('X')->template
						= '<xsl:value-of select="$xxx"/>';
				}
			],
			[
				'<r xmlns:html="urn:s9e:TextFormatter:html"><html:b>...</html:b></r>',
				function ($configurator)
				{
					$configurator->tags->add('html:b')->template
						= '<b><xsl:apply-templates /></b>';
				}
			],
			[
				'<r><E>:)</E><E>:(</E></r>',
				function ($configurator)
				{
					$configurator->tags->add('E')->template
						= '<xsl:choose><xsl:when test=".=\':)\'"><img src="happy.png" alt=":)"/></xsl:when><xsl:when test=".=\':(\'"><img src="sad.png" alt=":("/></xsl:when><xsl:otherwise><xsl:value-of select="."/></xsl:otherwise></xsl:choose>';
				}
			],
			[
				'<r><E>:)</E><E>:(</E><E>:-)</E></r>',
				function ($configurator)
				{
					$configurator->tags->add('E')->template
						= '<xsl:choose><xsl:when test=".=\':)\'or.=\':-)\'"><img src="happy.png" alt=":)"/></xsl:when><xsl:when test=".=\':(\'"><img src="sad.png" alt=":("/></xsl:when><xsl:otherwise><xsl:value-of select="."/></xsl:otherwise></xsl:choose>';
				}
			],
			[
				'<r>x <X/> y</r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template
						= '<xsl:text>&amp;foo</xsl:text>';
				}
			],
			[
				'<r>x <X>...</X> y</r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template
						= '<b>x <i>i</i> <u>u</u> y</b>';
				}
			],
			[
				'<r><X foo="FOO"/></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template =
						'<b><xsl:attribute name="title">
							<xsl:choose>
								<xsl:when test="@foo">foo=<xsl:value-of select="@foo"/>;</xsl:when>
								<xsl:otherwise>bar=<xsl:value-of select="@bar"/>;</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute></b>';
				}
			],
			[
				'<r><X foo="FOO"/></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template =
						'<b><xsl:attribute name="title">
							<xsl:if test="@foo">foo=<xsl:value-of select="@foo"/>;</xsl:if>
							<xsl:if test="@bar">bar=<xsl:value-of select="@bar"/>;</xsl:if>
						</xsl:attribute></b>';
				}
			],
			[
				'<r><X foo="FOO"/></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template =
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
				'<r><X foo="FOO">..</X></r>',
				function ($configurator, $test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}

					$configurator->tags->add('X')->template
						= '<xsl:value-of select="string-length(@foo)"/>';
				}
			],
			[
				'<r><X foo="FOO">..</X></r>',
				function ($configurator, $test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}

					$configurator->tags->add('X')->template
						= '<xsl:value-of select="string-length()"/>';
				}
			],
			[
				'<r><X foo="FOO">..</X></r>',
				function ($configurator, $test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}

					$configurator->tags->add('X')->template
						= '<xsl:value-of select="string-length(@bar)"/>';
				}
			],
			[
				'<r><X foo="ABCDEF0153">..</X></r>',
				function ($configurator, $test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}

					$configurator->tags->add('X')->template
						= '<xsl:value-of select="substring(@foo,2,3)"/>';
				}
			],
			[
				'<r><X foo="ABCDEF0153">..</X></r>',
				function ($configurator, $test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}

					$configurator->tags->add('X')->template
						= '<xsl:value-of select="substring(@foo,0,3)"/>';
				}
			],
			[
				'<r><X foo="ABCDEF0153">..</X></r>',
				function ($configurator, $test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}

					$configurator->tags->add('X')->template
						= '<xsl:value-of select="substring(@foo,2,-3)"/>';
				}
			],
			[
				'<r><X foo="ABCDEF0153" x="3">..</X></r>',
				function ($configurator, $test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}

					$configurator->tags->add('X')->template
						= '<xsl:value-of select="substring(@foo,@x)"/>';
				}
			],
			[
				'<r><X foo="ABCDEF0153" x="3">..</X></r>',
				function ($configurator, $test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}

					$configurator->tags->add('X')->template
						= '<xsl:value-of select="substring(@foo,3,@x)"/>';
				}
			],
			[
				'<r><X data-foo="foo" data-bar="bar">..</X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template
						= '<hr><xsl:copy-of select="@*"/></hr>';
				}
			],
			[
				'<r><X x=""></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template = '<xsl:if test="@x">x</xsl:if>';
				}
			],
			[
				'<r><X x="" y="" z=""></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template
						= '<div><xsl:copy-of select="@z"/></div>';
				}
			],
			[
				'<r><X/></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template = '<b>X</b>';
				}
			],
			[
				'<r><X x="a&amp;b"/></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template
						= '<xsl:value-of select="substring-after(@x,\'&amp;\')"/>';
				}
			],
			[
				'<r><X x="a/b"/></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template
						= '<xsl:value-of select="substring-after(@x,\'+\')"/>';
				}
			],
			[
				'<r><X x="a&amp;b"/></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template
						= '<xsl:value-of select="substring-before(@x,\'&amp;\')"/>';
				}
			],
			[
				'<r><X foo="\'"/></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template
						= '<xsl:if test="@foo = &quot;\'&quot;">ok</xsl:if>';
				}
			],
			[
				'<r><X foo="\'"/></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template
						= '<xsl:if test="&quot;\'&quot; = @foo">ok</xsl:if>';
				}
			],
			[
				'<r><X foo="\'"/></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template
						= '<xsl:if test="contains(@foo, &quot;\'&quot;)">ok</xsl:if>';
				}
			],
			[
				'<r><X foo="\'"/></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template
						= '<xsl:if test="contains(&quot;\'&quot;, @foo)">ok</xsl:if>';
				}
			],
			[
				// NOTE: this XML cannot be produced by the parser as it does not escape
				//       single quotes. It would have to be generated or modified manually
				'<r><X foo="&#39;&quot;"/></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template
						= '<xsl:value-of select="@foo"/><xsl:if test="contains(@foo, &quot;\'&quot;) and contains(@foo, \'&quot;\')">ok</xsl:if>';
				}
			],
			[
				'<r><X/></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template
						= '<script>0&lt;1 &amp;&amp; 1&lt;0 &amp;&amp; alert(1)</script>';
				}
			],
			[
				'<r><X/></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template
						= '<script><xsl:value-of select="\'0&lt;1 &amp;&amp; 1&lt;0 &amp;&amp; alert(1)\'"/></script>';
				}
			],
		];
	}

	protected function runCodeTest($template, $contains, $notContains, $setup = null)
	{
		$this->configurator->tags->add('X')->template = $template;

		if (isset($setup))
		{
			call_user_func($setup, $this->configurator->rendering->engine, $this);
		}

		if (isset($contains))
		{
			foreach ((array) $contains as $str)
			{
				$this->assertContains($str, $this->getRendererSource());
			}
		}

		if (isset($notContains))
		{
			foreach ((array) $notContains as $str)
			{
				$this->assertNotContains($str, $this->getRendererSource());
			}
		}
	}

	/**
	* @dataProvider getXPathTests
	* @testdox XPath expressions are inlined as PHP whenever possible
	*/
	public function testXPath($template, $contains = null, $notContains = null, $setup = null)
	{
		$this->runCodeTest($template, $contains, $notContains, $setup);
	}

	public function getXPathTests()
	{
		return [
			// XPath in values
			[
				'<xsl:value-of select="@bar"/>',
				"\$node->getAttribute('bar')"
			],
			[
				'<xsl:value-of select="."/>',
				"\$node->textContent"
			],
			[
				'<xsl:value-of select="$foo"/>',
				"\$this->params['foo']"
			],
			[
				'<xsl:value-of select="\'foo\'"/>',
				null,
				'$this->xpath->evaluate'
			],
			[
				'<xsl:value-of select="local-name()"/>',
				'$node->localName',
				'$this->xpath->evaluate'
			],
			[
				'<xsl:value-of select="name()"/>',
				'$node->nodeName',
				'$this->xpath->evaluate'
			],
			[
				'<xsl:value-of select="\'foo\'"/>',
				"\$this->out.='foo';"
			],
			[
				'<xsl:value-of select=\'"foo"\'/>',
				"\$this->out.='foo';"
			],
			[
				'<xsl:value-of select="123"/>',
				"\$this->out.='123';"
			],
			[
				'<xsl:value-of select="string-length(@bar)"/>',
				"mb_strlen(\$node->getAttribute('bar'),'utf-8')",
				'string-length',
				function ($test)
				{
					if (!extension_loaded('mbstring'))
					{
						$this->markTestSkipped('Extension mbstring is required.');
					}
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						$this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
					}
				}
			],
			// XPath in conditions
			[
				'<xsl:if test="@foo">Foo</xsl:if>',
				"if(\$node->hasAttribute('foo'))"
			],
			[
				'<xsl:if test="$a+$b=$c">...</xsl:if>',
				['$this->xpath = new \\DOMXPath($dom);', 'function getParamAsXPath(']
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
			'<xsl:value-of select="string-length(@foo)"/>',
			null,
			'mb_strlen',
			function ($generator)
			{
				$generator->useMultibyteStringFunctions = false;
			}
		);
	}

	/**
	* @testdox Calls the optimizer's optimize() method if applicable
	*/
	public function testCallsOptimizer()
	{
		$mock = $this->getMockBuilder('stdClass')
		             ->setMethods(['optimize'])
		             ->getMock();
		$mock->expects($this->atLeastOnce())
		     ->method('optimize')
		     ->will($this->returnArgument(0));

		$this->configurator->rendering->engine->optimizer = $mock;
		$this->configurator->rendering->getRenderer();
	}

	/**
	* @testdox Can run without an optimizer
	*/
	public function testNoOptimizer()
	{
		unset($this->configurator->rendering->engine->optimizer);
		$this->configurator->rendering->getRenderer();
	}

	/**
	* @testdox Calls the control structures optimizer's optimize() method if applicable
	*/
	public function testCallsControlStructuresOptimizer()
	{
		$mock = $this->getMockBuilder('stdClass')
		             ->setMethods(['optimize'])
		             ->getMock();
		$mock->expects($this->once())
		     ->method('optimize')
		     ->will($this->returnArgument(0));

		$this->configurator->rendering->engine->controlStructuresOptimizer = $mock;
		$this->configurator->rendering->getRenderer();
	}

	/**
	* @testdox Can run without a control structures optimizer
	*/
	public function testNoControlStructuresOptimizer()
	{
		unset($this->configurator->rendering->engine->controlStructuresOptimizer);
		$this->configurator->rendering->getRenderer();
	}

	/**
	* @requires extension tokenizer
	* @dataProvider getOptimizationTests
	* @testdox Code optimization tests
	*/
	public function testOptimizations($xsl, $contains = null, $notContains = null, $setup = null)
	{
		$this->runCodeTest($xsl, $contains, $notContains, $setup);
	}

	public function getOptimizationTests()
	{
		return [
			[
				'<br/>',
				"\$this->out.='<br>';"
			],
			[
				'<xsl:text/>',
				"'X'",
				"\$this->out.='';"
			],
			[
				'<xsl:choose>
					<xsl:when test="@foo">foo</xsl:when>
					<xsl:otherwise><xsl:text/></xsl:otherwise>
				</xsl:choose>',
				"{if(\$node->hasAttribute('foo'))\$this->out.='foo';}",
				['else{}', 'else;', "\$this->out.=''"]
			],
			[
				'<xsl:value-of select="\'foo\'"/>',
				"\$this->out.='foo';"
			],
			[
				'<xsl:value-of select="\'fo\\o\'"/>',
				"\$this->out.='fo\\\\o';"
			],
			[
				'<xsl:value-of select="\'&quot;&lt;AT&amp;T&gt;\'"/>',
				"\$this->out.='\"&lt;AT&amp;T&gt;';"
			],
			[
				'<xsl:value-of select="&quot;&apos;&lt;AT&amp;T&gt;&quot;"/>',
				"\$this->out.='\\'&lt;AT&amp;T&gt;';"
			],
			[
				'<b title="{\'&quot;foo&quot;\'}"></b>',
				var_export('<b title="&quot;foo&quot;"></b>', true)
			],
			[
				'<b title="{&quot;&apos;foo&apos;&quot;}"></b>',
				var_export('<b title="\'foo\'"></b>', true)
			],
			[
				'<xsl:value-of select="local-name()"/>',
				'$this->out.=$node->localName',
				'htmlspecialchars($node->localName'
			],
			[
				'<xsl:value-of select="name()"/>',
				'$this->out.=$node->nodeName',
				'htmlspecialchars($node->nodeName'
			],
			[
				// This test ensures that we concatenate inside the htmlspecialchars() call rather
				// than concatenate the result of two htmlspecialchars() calls
				'<xsl:value-of select="@foo"/><xsl:value-of select="@bar"/>',
				"htmlspecialchars(\$node->getAttribute('foo').\$node->getAttribute('bar')"
			],
			[
				// This test ensures that we pre-escape literals before merging htmlspecialchars()
				// calls together
				'<img src="{$PATH}/bar.png"/>',
				"'<img src=\"'.htmlspecialchars(\$this->params['PATH'],2).'/bar.png\">'"
			],
			[
				'Hi',
				null,
				'getParamAsXPath'
			],
			[
				'<xsl:apply-templates/>',
				null,
				'$this->xpath'
			],
			[
				'<xsl:apply-templates select="*"/>',
				'$this->xpath = new \\DOMXPath',
				'getParamAsXPath'
			],
		];
	}

	/**
	* @testdox HTML rendering
	* @dataProvider getConformanceTests
	*/
	public function testHTML($xml, $html, $setup = null, $rendererSetup = null)
	{
		if (isset($setup))
		{
			$setup($this->configurator);
		}

		extract($this->configurator->finalize());

		if (isset($rendererSetup))
		{
			$rendererSetup($renderer);
		}

		$this->assertSame($html, $renderer->render($xml));
	}

	public function getConformanceTests()
	{
		return [
			[
				'<t>Plain text</t>',
				'Plain text'
			],
			[
				"<t>Multi<br/>\nline</t>",
				"Multi<br>\nline"
			],
			[
				'<r>x <B><s>[b]</s>bold<e>[/b]</e></B> y</r>',
				'x <b>bold</b> y',
				function ($configurator)
				{
					$configurator->tags->add('B')->template = '<b><xsl:apply-templates/></b>';
				}
			],
			[
				'<r>x <T1>[t1/]</T1> y</r>',
				'x <b foo="FOO"><i>!</i><i>?</i></b> y',
				function ($configurator)
				{
					$configurator->tags->add('T1')->template =
						'<b>
							<xsl:choose>
								<xsl:when test="1">
									<xsl:attribute name="foo">FOO</xsl:attribute>
								</xsl:when>
								<xsl:otherwise>
									<i>!</i>
								</xsl:otherwise>
							</xsl:choose>
							<xsl:choose>
								<xsl:when test="0">
									<xsl:attribute name="bar"/>
								</xsl:when>
								<xsl:otherwise>
									<i>!</i>
								</xsl:otherwise>
							</xsl:choose>
							<i>?</i>
						</b>';
				}
			],
			[
				'<r>x <URL url="http://google.com"><s>[url="http://google.com"]</s>google<e>[/url]</e></URL> y</r>',
				'x <a href="http://google.com">google</a> y',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('URL');
				}
			],
			[
				'<r><B><s>[b]</s>...<e>[/b]</e></B><T2><s>[t2]</s><B><s>[b]</s>...<e>[/b]</e></B><I><s>[i]</s>...<e>[/i]</e></I><e>[/t2]</e></T2></r>',
				'<b>...</b><b><i>...</i></b>',
				function ($configurator)
				{
					$configurator->tags->add('B')->template = '<b><xsl:apply-templates/></b>';
					$configurator->tags->add('I')->template = '<i><xsl:apply-templates/></i>';
					$configurator->tags->add('T2')->template = '<b><xsl:apply-templates select="I"/></b>';
				}
			],
			[
				'<r>x <HR>[hr/]</HR> y</r>',
				'x <hr> y',
				function ($configurator)
				{
					$configurator->tags->add('HR')->template = '<hr/>';
				}
			],
			[
				'<r><QUOTE author="foo"><s>[quote="foo"]</s>
	<QUOTE author="bar"><s>[quote="bar"]</s>...<e>[/quote]</e></QUOTE>
....
<e>[/quote]</e></QUOTE>
!!!!		</r>',
				'<blockquote><div><cite>foo wrote:</cite>
	<blockquote><div><cite>bar wrote:</cite>...</div></blockquote>
....
</div></blockquote>
!!!!		',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('QUOTE');
				}
			],
			[
				'<r><QUOTE author="foo"><s>[quote="foo"]</s>
	<QUOTE><s>[quote]</s>...<e>[/quote]</e></QUOTE>
....
<e>[/quote]</e></QUOTE>
!!!!		</r>',
				'<blockquote><div><cite>foo wrote:</cite>
	<blockquote class="uncited"><div>...</div></blockquote>
....
</div></blockquote>
!!!!		',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('QUOTE');
				}
			],
			[
				'<r>x <B>[b/]</B> y</r>',
				'x <b>[b/]</b> y',
				function ($configurator)
				{
					$configurator->tags->add('B')->template = '<b><xsl:apply-templates/></b>';
				}
			],
			[
				'<r><T3 bar="BAR"><s>[t3 bar="BAR"]</s>...<e>[/t3]</e></T3></r>',
				'<b title="foo BAR {baz}">...</b>',
				function ($configurator)
				{
					$configurator->tags->add('T3')->template = '<b title="foo {@bar} {{baz}}"><xsl:apply-templates /></b>';
				}
			],
			[
				'<r><T4><s>[t4]</s>...<e>[/t4]</e></T4></r>',
				'<b title="foo [t4] {baz}">...</b>',
				function ($configurator)
				{
					$configurator->tags->add('T4')->template = '<b title="foo {s} {{baz}}"><xsl:apply-templates /></b>';
				},
				function ($renderer)
				{
					$renderer->metaElementsRegexp = '((?!))';
				}
			],
			[
				'<r><T5><s>[t5]</s>...<e>[/t5]</e></T5></r>',
				'<b title="foo [t5]...[/t5] {baz}">...</b>',
				function ($configurator)
				{
					$configurator->tags->add('T5')->template = '<b title="foo {.} {{baz}}"><xsl:apply-templates /></b>';
				},
				function ($renderer)
				{
					$renderer->metaElementsRegexp = '((?!))';
				}
			],
			[
				'<r><T6><s>[t6]</s>...<e>[/t6]</e></T6></r>',
				'<b title="">...</b>',
				function ($configurator)
				{
					$configurator->tags->add('T6')->template = '<b title=""><xsl:apply-templates /></b>';
				}
			],
			[
				'<r><T7><s>[t7]</s>...<e>[/t7]</e></T7></r>',
				'<b title="}}}">...</b>',
				function ($configurator)
				{
					$configurator->tags->add('T7')->template = '<b title="{concat(\'}\',\'}}\')}"><xsl:apply-templates /></b>';
				}
			],
			[
				'<r><T8><s>[t8]</s>...<e>[/t8]</e></T8></r>',
				'<b>xy</b>',
				function ($configurator)
				{
					$configurator->tags->add('T8')->template = '<b>x<xsl:value-of select="\'\'" />y</b>';
				}
			],
			[
				'<r><B><s>[b]</s>&lt;&gt;\'"&amp;<e>[/b]</e></B></r>',
				'<b>&lt;&gt;\'"&amp;</b>',
				function ($configurator)
				{
					$configurator->tags->add('B')->template = '<b><xsl:apply-templates/></b>';
				}
			],
			[
				'<r><T9 a="&lt;&gt;\'&quot;&amp;"><s>[t9 a="&lt;&gt;\'\\"&amp;"]</s>...<e>[/t9]</e></T9></r>',
				'<b data-a="&lt;&gt;\'&quot;&amp;">...</b>',
				function ($configurator)
				{
					$configurator->tags->add('T9')->template = '<b data-a="{@a}"><xsl:apply-templates /></b>';
				}
			],
			[
				'<r><T10 a="&lt;&gt;\'&quot;&amp;"><s>[t10 a="&lt;&gt;\'\\"&amp;"]</s>...<e>[/t10]</e></T10></r>',
				'<b data-a="&lt;&gt;\'&quot;&amp;">...</b>',
				function ($configurator)
				{
					$configurator->tags->add('T10')->template = '<b><xsl:attribute name="data-a"><xsl:value-of select="@a" /></xsl:attribute><xsl:apply-templates /></b>';
				}
			],
			[
				'<r><T11 a="&lt;&gt;\'&quot;&amp;"><s>[t11 a="&lt;&gt;\'\\"&amp;"]</s>...<e>[/t11]</e></T11></r>',
				'<b>&lt;&gt;\'"&amp;</b>',
				function ($configurator)
				{
					$configurator->tags->add('T11')->template = '<b><xsl:value-of select="@a" /></b>';
				}
			],
			[
				'<r><T12><s>[t12]</s>...<e>[/t12]</e></T12></r>',
				'<b data-a="&quot;\'&lt;&gt;&amp;">...</b>',
				function ($configurator)
				{
					$configurator->tags->add('T12')->template = '<b data-a="&quot;\'&lt;&gt;&amp;"><xsl:apply-templates /></b>';
				}
			],
			[
				'<r><T13><s>[t13]</s>...<e>[/t13]</e></T13></r>',
				'<b data-a="&quot;\'&lt;&gt;&amp;">...</b>',
				function ($configurator)
				{
					$configurator->tags->add('T13')->template = '<b><xsl:attribute name="data-a">&quot;\'&lt;&gt;&amp;</xsl:attribute><xsl:apply-templates /></b>';
				}
			],
			[
				'<r><T14>[t14/]</T14></r>',
				'<b>"\'&lt;&gt;&amp;</b>',
				function ($configurator)
				{
					$configurator->tags->add('T14')->template = '<b>&quot;\'&lt;&gt;&amp;</b>';
				}
			],
			[
				'<r><T15>[t15/]</T15></r>',
				'<b>"\'&lt;&gt;&amp;</b>',
				function ($configurator)
				{
					$configurator->tags->add('T15')->template = '<b><xsl:value-of select="concat(\'&quot;\',&quot;\'&lt;&gt;&amp;&quot;)" /></b>';
				}
			],
			[
				'<r><T16 a="&lt;&gt;\'&quot;&amp;"><s>[t16 a="&lt;&gt;\'\\"&amp;"]</s>...<e>[/t16]</e></T16></r>',
				'<b data-a="&lt;&gt;\'&quot;&amp;&lt;&gt;\'&quot;&amp;&lt;&gt;\'&quot;&amp;">...</b>',
				function ($configurator)
				{
					$configurator->tags->add('T16')->template = '<b data-a="{@a}{@a}{@a}"><xsl:apply-templates /></b>';
				}
			],
			[
				'<r><T17 a="&lt;&gt;\'&quot;&amp;"><s>[t17 a="&lt;&gt;\'\\"&amp;"]</s>...<e>[/t17]</e></T17></r>',
				'<b>&lt;&gt;\'"&amp;&lt;&gt;\'"&amp;&lt;&gt;\'"&amp;</b>',
				function ($configurator)
				{
					$configurator->tags->add('T17')->template = '<b><xsl:value-of select="@a" /><xsl:value-of select="@a" /><xsl:value-of select="@a" /></b>';
				}
			],
			[
				'<r>x <IMG src="http://example.com/foo.png"><s>[img]</s>http://example.com/foo.png<e>[/img]</e></IMG> y</r>',
				'x <img src="http://example.com/foo.png" title="" alt=""> y',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('IMG');
				}
			],
			[
				'<r><LIST><s>[list]</s>[*]one[*]two<e>[/list]</e></LIST></r>',
				'<ul>[*]one[*]two</ul>',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('LIST');
				}
			],
			[
				'<r><LIST type="decimal"><s>[list=1]</s>[*]one[*]two<e>[/list]</e></LIST></r>',
				'<ol style="list-style-type:decimal">[*]one[*]two</ol>',
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('LIST');
				}
			],
			[
				'<r>x <T18><s>[T18]</s> ... <e>[/T18]</e></T18> y</r>',
				'x <!-- ... --> y',
				function ($configurator)
				{
					$configurator->tags->add('T18')->template = '<xsl:comment><xsl:apply-templates/></xsl:comment>';
				}
			],
			[
				'<r><X/></r>',
				'N',
				function ($configurator)
				{
					$configurator->tags->add('X')->template = '<xsl:choose><xsl:when test="@*">Y</xsl:when><xsl:otherwise>N</xsl:otherwise></xsl:choose>';
				}
			],
			[
				'<r><X a=""/></r>',
				'Y',
				function ($configurator)
				{
					$configurator->tags->add('X')->template = '<xsl:choose><xsl:when test="@*">Y</xsl:when><xsl:otherwise>N</xsl:otherwise></xsl:choose>';
				}
			],
			[
				'<r><X/></r>',
				'<x:svg xmlns:x="http://www.w3.org/2000/svg" width="100" height="100" viewbox="0 0 100 100"></x:svg>',
				function ($configurator)
				{
					$configurator->tags->add('X')->template = '<x:svg xmlns:x="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"></x:svg>';
				}
			],
		];
	}

	/**
	* @dataProvider getVoidTests
	* @testdox Rendering of void and empty elements in HTML
	*/
	public function testVoidHTML($xml, $template, $html)
	{
		$this->configurator->tags->add('FOO')->template = new UnsafeTemplate($template);
		extract($this->configurator->finalize());
		$this->assertSame($html, $renderer->render($xml));
	}

	public function getVoidTests($type)
	{
		return [
			[
				'<r><FOO/></r>',
				'<hr id="foo"/>',
				'<hr id="foo">'
			],
			[
				'<r><FOO/></r>',
				'<hr id="foo">foo</hr>',
				'<hr id="foo">'
			],
			[
				'<r><FOO/></r>',
				'<hr id="foo"><xsl:apply-templates/></hr>',
				'<hr id="foo">'
			],
			[
				'<r><FOO/></r>',
				'<hr id="foo"><xsl:value-of select="@name"/></hr>',
				'<hr id="foo">'
			],
			[
				'<r><FOO/></r>',
				'<div id="foo"/>',
				'<div id="foo"></div>'
			],
			[
				'<r><FOO/></r>',
				'<div id="foo">foo</div>',
				'<div id="foo">foo</div>'
			],
			[
				'<r><FOO/></r>',
				'<div id="foo"><xsl:apply-templates/></div>',
				'<div id="foo"></div>'
			],
			[
				'<r><FOO/></r>',
				'<div id="foo"><xsl:value-of select="@name"/></div>',
				'<div id="foo"></div>'
			],
			[
				'<r><FOO name="hr"/></r>',
				'<xsl:element name="{@name}"><xsl:attribute name="id">foo</xsl:attribute></xsl:element>',
				'<hr id="foo">'
			],
			[
				'<r><FOO name="hr"/></r>',
				'<xsl:element name="{@name}"><xsl:attribute name="id">foo</xsl:attribute>foo</xsl:element>',
				'<hr id="foo">'
			],
			[
				'<r><FOO name="hr"/></r>',
				'<xsl:element name="{@name}"><xsl:attribute name="id">foo</xsl:attribute><xsl:apply-templates/></xsl:element>',
				'<hr id="foo">'
			],
			[
				'<r><FOO name="hr"/></r>',
				'<xsl:element name="{@name}"><xsl:attribute name="id">foo</xsl:attribute><xsl:value-of select="@name"/></xsl:element>',
				'<hr id="foo">'
			],
			[
				'<r><FOO name="div"/></r>',
				'<xsl:element name="{@name}"><xsl:attribute name="id">foo</xsl:attribute></xsl:element>',
				'<div id="foo"></div>'
			],
			[
				'<r><FOO name="div"/></r>',
				'<xsl:element name="{@name}"><xsl:attribute name="id">foo</xsl:attribute>foo</xsl:element>',
				'<div id="foo">foo</div>'
			],
			[
				'<r><FOO name="div"/></r>',
				'<xsl:element name="{@name}"><xsl:attribute name="id">foo</xsl:attribute><xsl:apply-templates/></xsl:element>',
				'<div id="foo"></div>'
			],
			[
				'<r><FOO name="div"/></r>',
				'<xsl:element name="{@name}"><xsl:attribute name="id">foo</xsl:attribute><xsl:value-of select="@name"/></xsl:element>',
				'<div id="foo">div</div>'
			]
		];
	}

	/**
	* @testdox Tests from plugins
	* @dataProvider getPluginsTests
	*/
	public function testPlugins($pluginName, $original, $expected, array $pluginOptions = [], $setup = null)
	{
		$this->configurator->rendering->engine = 'PHP';
		$this->configurator->rendering->engine->enableQuickRenderer = false;
		$plugin = $this->configurator->plugins->load($pluginName, $pluginOptions);

		if ($setup)
		{
			$setup($this->configurator, $plugin);
		}

		if ($pluginName === 'BBCodes')
		{
			// Capture the names of the BBCodes used
			preg_match_all('/\\[([*\\w]+)/', $original, $matches);

			foreach ($matches[1] as $bbcodeName)
			{
				if (!isset($this->configurator->BBCodes[$bbcodeName]))
				{
					$this->configurator->BBCodes->addFromRepository($bbcodeName);
				}
			}
		}

		extract($this->configurator->finalize());

		$this->assertSame($expected, $renderer->render($parser->parse($original)));
	}

	/**
	* @testdox Tests from plugins (Quick renderer)
	* @dataProvider getPluginsTests
	* @requires extension tokenizer
	* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\Quick
	*/
	public function testPluginsQuick($pluginName, $original, $expected, array $pluginOptions = [], $setup = null)
	{
		$this->testPlugins(
			$pluginName,
			$original,
			$expected,
			$pluginOptions,
			function ($configurator, $plugin) use ($setup)
			{
				$configurator->rendering->engine->enableQuickRenderer = true;

				if (isset($setup))
				{
					$setup($configurator, $plugin);
				}
			}
		);
	}

	public function getPluginsTests()
	{
		$pluginsDir = __DIR__ . '/../../Plugins';

		$tests = [];
		foreach (glob($pluginsDir . '/*', GLOB_ONLYDIR) as $dirpath)
		{
			$pluginName = basename($dirpath);
			$className  = 's9e\\TextFormatter\\Tests\\Plugins\\' . $pluginName . '\\ParserTest';

			$obj = new $className;
			if (is_callable([$obj, 'getRenderingTests']))
			{
				foreach ($obj->getRenderingTests() as $test)
				{
					array_unshift($test, $pluginName);
					$tests[] = $test;
				}
			}
		}

		$obj = new BBCodesTest;
		foreach ($obj->getPredefinedBBCodesTests() as $test)
		{
			// Insert an empty array for pluginOptions
			if (isset($test[2]))
			{
				$test[3] = $test[2];
				$test[2] = [];
			}

			array_unshift($test, 'BBCodes');
			$tests[] = $test;
		}

		return $tests;
	}

	/**
	* @requires extension tokenizer
	* @testdox Creates a Quick renderer if $enableQuickRenderer is true
	*/
	public function testQuickRenderer()
	{
		$this->configurator->rendering->engine->enableQuickRenderer = true;
		$this->configurator->tags->add('B')->template = '<b><xsl:apply-templates/></b>';

		$this->assertContains('renderQuick', $this->getRendererSource());
	}

	/**
	* @testdox Does not create a Quick renderer if $enableQuickRenderer is false
	*/
	public function testNoQuickRenderer()
	{
		$this->configurator->rendering->engine->enableQuickRenderer = false;
		$this->configurator->tags->add('B')->template = '<b><xsl:apply-templates/></b>';

		$this->assertNotContains('renderQuick', $this->getRendererSource());
	}

	/**
	* @testdox Saves the branch tables from the serializer if applicable
	*/
	public function testBranchTables()
	{
		$this->configurator->rendering->engine->enableQuickRenderer = false;
		$this->configurator->tags->add('X')->template = 
			'<xsl:choose>
				<xsl:when test="@foo=1">1</xsl:when>
				<xsl:when test="@foo=2">2</xsl:when>
				<xsl:when test="@foo=3">3</xsl:when>
				<xsl:when test="@foo=4">4</xsl:when>
				<xsl:when test="@foo=5">5</xsl:when>
				<xsl:when test="@foo=6">6</xsl:when>
				<xsl:when test="@foo=7">7</xsl:when>
				<xsl:when test="@foo=8">8</xsl:when>
			</xsl:choose>';

		$renderer = $this->configurator->rendering->getRenderer();

		$this->assertContains(
			'protected static $bt13027555=[1=>0,2=>1,3=>2,4=>3,5=>4,6=>5,7=>6,8=>7];',
			$this->getRendererSource()
		);
	}
}