<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP;

use s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\Optimizer;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\Quick;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\Serializer;
use s9e\TextFormatter\Tests\RendererTests;
use s9e\TextFormatter\Tests\Test;

/**
* @requires extension tokenizer
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\Quick
* @covers s9e\TextFormatter\Renderers\PHP
*/
class QuickTest extends Test
{
	use RendererTests;

	protected function setUp(): void
	{
		parent::setUp();
		$this->configurator->rendering->engine = 'PHP';
		$this->configurator->rendering->engine->enableQuickRenderer = true;
	}

	protected function getPHP($template)
	{
		$serializer = new Serializer;
		$optimizer  = new Optimizer;

		$ir  = TemplateParser::parse($template);
		$php = $serializer->serialize($ir->documentElement);
		$php = $optimizer->optimize($php);

		return $php;
	}

	protected function getRenderer()
	{
		extract($this->configurator->finalize());

		return $renderer;
	}

	/**
	* @testdox The quick renderer can handle unknown tags
	*/
	public function testQuickRendererUnknownTag()
	{
		$renderer = $this->getRenderer();
		$this->assertSame('unknown', $renderer->render('<r><UNKNOWN>unknown</UNKNOWN></r>'));
	}

	/**
	* @testdox The quick renderer can handle unknown self-closing tags
	*/
	public function testQuickRendererUnknownSelfClosingTag()
	{
		$renderer = $this->getRenderer();
		$this->assertSame('unknown', $renderer->render('<r><UNKNOWN/>unknown</r>'));
	}

	/**
	* @testdox The quick renderer can handle comments
	*/
	public function testQuickRendererComment()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('comments');

		$renderer = $this->getRenderer();
		$this->assertSame('unknown', $renderer->render('<r><!---->unknown</r>'));
	}

	/**
	* @testdox The quick renderer can handle processing instructions
	*/
	public function testQuickRendererProcessingInstruction()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Processing');

		$renderer = $this->getRenderer();
		$this->assertSame('unknown', $renderer->render('<r><?x ?>unknown</r>'));
	}

	/**
	* @dataProvider getRenderingTests
	*/
	public function testRendering($templates, $xml, $expected, $setup = null)
	{
		if (isset($setup))
		{
			$setup();
		}

		$compiledTemplates = [];
		foreach ($templates as $tagName => $template)
		{
			$compiledTemplates[$tagName] = self::getPHP($template);
		}

		$php = Quick::getSource($compiledTemplates);
		$className = 'quick_' . md5($php);

		if (!class_exists($className))
		{
			eval('class ' . $className . ' extends s9e\\TextFormatter\\Renderers\\PHP{public function renderNode(DOMNode $node){}' . $php . '}');
		}

		$renderer = new $className;
		$this->assertSame($expected, $renderer->render($xml));
	}

	public static function getRenderingTests()
	{
		return [
			[
				[
					'B' => '<b><xsl:apply-templates/></b>',
					'I' => '<i><xsl:apply-templates/></i>',
					'U' => '<u><xsl:apply-templates/></u>'
				],
				'<r>Hello <B><s>[b]</s>world<e>[/b]</e></B>!<br/>x</r>',
				'Hello <b>world</b>!<br>x'
			],
			[
				[
					'B' => '<b><xsl:apply-templates/></b>'
				],
				'<r><p>Hello <B><s>[b]</s>world<e>[/b]</e></B>!<br/>x</p></r>',
				'<p>Hello <b>world</b>!<br>x</p>'
			],
			[
				[
					'URL' => '<a href="{@url}"><xsl:apply-templates/></a>'
				],
				'<r>Go to <URL url="http://example.org"><s>[url]</s>http://example.org<e>[/url]</e></URL>!</r>',
				'Go to <a href="http://example.org">http://example.org</a>!'
			],
			[
				[
					'QUOTE' => self::ws(
						'<blockquote>
							<xsl:if test="not(@author)">
								<xsl:attribute name="class">uncited</xsl:attribute>
							</xsl:if>
							<div>
								<xsl:if test="@author">
									<cite><xsl:value-of select="@author"/> wrote:</cite>
								</xsl:if>
								<xsl:apply-templates/>
							</div>
						</blockquote>'
					)
				],
				'<r><QUOTE author="John Doe"><s>[quote="John Doe"]</s>...<e>[/quote]</e></QUOTE></r>',
				'<blockquote><div><cite>John Doe wrote:</cite>...</div></blockquote>'
			],
			[
				[
					'LI'   => '<li><xsl:apply-templates/></li>',
					'LIST' => self::ws(
						'<xsl:choose>
							<xsl:when test="not(@type)">
								<ul><xsl:apply-templates /></ul>
							</xsl:when>
							<xsl:when test="starts-with(@type,\'decimal-\') or starts-with(@type,\'lower-\') or starts-with(@type,\'upper-\')">
								<ol style="list-style-type:{@type}"><xsl:apply-templates /></ol>
							</xsl:when>
							<xsl:otherwise>
								<ul style="list-style-type:{@type}"><xsl:apply-templates /></ul>
							</xsl:otherwise>
						</xsl:choose>'
					)
				],
				'<r><LIST><s>[list]</s><LI><s>[*]</s>one</LI><LI><s>[*]</s>two</LI><LI><s>[*]</s>three</LI><e>[/list]</e></LIST></r>',
				'<ul><li>one</li><li>two</li><li>three</li></ul>'
			],
			[
				[
					'LI'   => '<li><xsl:apply-templates/></li>',
					'LIST' => self::ws(
						'<xsl:choose>
							<xsl:when test="not(@type)">
								<ul><xsl:apply-templates /></ul>
							</xsl:when>
							<xsl:when test="starts-with(@type,\'decimal-\') or starts-with(@type,\'lower-\') or starts-with(@type,\'upper-\')">
								<ol style="list-style-type:{@type}"><xsl:apply-templates /></ol>
							</xsl:when>
							<xsl:otherwise>
								<ul style="list-style-type:{@type}"><xsl:apply-templates /></ul>
							</xsl:otherwise>
						</xsl:choose>'
					)
				],
				'<r><LIST type="upper-roman"><s>[list]</s><LI><s>[*]</s>one</LI><LI><s>[*]</s>two</LI><LI><s>[*]</s>three</LI><e>[/list]</e></LIST></r>',
				'<ol style="list-style-type:upper-roman"><li>one</li><li>two</li><li>three</li></ol>'
			],
			[
				[
					'LI'   => '<li><xsl:apply-templates/></li>',
					'LIST' => self::ws(
						'<xsl:choose>
							<xsl:when test="not(@type)">
								<ul><xsl:apply-templates /></ul>
							</xsl:when>
							<xsl:when test="starts-with(@type,\'decimal-\') or starts-with(@type,\'lower-\') or starts-with(@type,\'upper-\')">
								<ol style="list-style-type:{@type}"><xsl:apply-templates /></ol>
							</xsl:when>
							<xsl:otherwise>
								<ul style="list-style-type:{@type}"><xsl:apply-templates /></ul>
							</xsl:otherwise>
						</xsl:choose>'
					)
				],
				'<r><LIST type="square"><s>[list]</s><LI><s>[*]</s>one</LI><LI><s>[*]</s>two</LI><LI><s>[*]</s>three</LI><e>[/list]</e></LIST></r>',
				'<ul style="list-style-type:square"><li>one</li><li>two</li><li>three</li></ul>'
			],
			[
				['IMG' => '<img src="{@url}"/>'],
				'<r><IMG url="foo.png">foo.png</IMG></r>',
				'<img src="foo.png">'
			],
			[
				['IMG' => '<img src="{@url}"/>'],
				'<r><IMG url="foo.png"/></r>',
				'<img src="foo.png">'
			],
			[
				['X' => '<hr title="{@x}"/>'],
				'<r><X x="&quot;\'&lt;&gt;&amp;"/></r>',
				'<hr title="&quot;\'&lt;&gt;&amp;">'
			],
			[
				['X' => '<xsl:value-of select="@x"/>'],
				'<r><X x="\'&quot;&lt;&gt;&amp;"/></r>',
				'\'"&lt;&gt;&amp;'
			],
			[
				['X' => '<xsl:if test="@x=\'&quot;&lt;&gt;\'">x</xsl:if>'],
				'<r><X x="&quot;&lt;&gt;"/></r>',
				'x'
			],
			[
				['X' => '<xsl:if test="\'&quot;&lt;&gt;\'=@x">x</xsl:if>'],
				'<r><X x="&quot;&lt;&gt;"/></r>',
				'x'
			],
			[
				['X' => '<xsl:if test="@x=1">x</xsl:if>'],
				'<r><X x="1"/><X x="2"/></r>',
				'x'
			],
			[
				['X' => '<xsl:if test="11=@x">x</xsl:if>'],
				'<r><X x="11"/><X x="2"/></r>',
				'x'
			],
			[
				['X' => '<hr title="{@x+200*@y}"/>'],
				'<r><X x="10" y="3"/></r>',
				'<hr title="610">'
			],
			[
				['X' => '<hr title="{100+@x}"/>'],
				'<r><X x="10" y="3"/></r>',
				'<hr title="110">'
			],
			[
				['X' => "<hr title=\"{translate(@x,'abc','ABC')}\"/>"],
				'<r><X x="&amp;amp;"/></r>',
				'<hr title="&amp;Amp;">'
			],
			[
				['X' => '<xsl:value-of select="@foo"/>'],
				'<r><X foo="FOO">foo="BAR"</X></r>',
				'FOO'
			],
			[
				['X' => '<xsl:value-of select="@foo"/>'],
				'<r><X> foo="BAR" </X></r>',
				''
			],
			[
				['X' => '<xsl:value-of select="count(@*)"/>'],
				'<r>x<XY>y</XY>z</r>',
				'xyz'
			],
			[
				['FOO' => '<xsl:apply-templates/><xsl:value-of select="@foo"/>'],
				'<r><FOO foo="attr">text</FOO></r>',
				'textattr'
			],
			[
				['X' => '<xsl:if test="@*">X<xsl:value-of select="@x"/></xsl:if>'],
				'<r><X x=""/></r>',
				'X'
			],
			[
				['X' => '<xsl:if test="@*">X<xsl:value-of select="@x"/></xsl:if>'],
				'<r><X/></r>',
				''
			],
		];
	}

	/**
	* @dataProvider getRenderingStrategyTests
	*/
	public function testRenderingStrategy($template, $expected, $setup = null)
	{
		if (isset($setup))
		{
			$setup();
		}
		$php = self::getPHP($template);
		$this->assertEquals($expected, Quick::getRenderingStrategy($php));
	}

	public static function getRenderingStrategyTests()
	{
		return [
			[
				'',
				[['static', '']]
			],
			[
				'foo',
				[['static', 'foo']]
			],
			[
				"'foo'",
				[['static', "'foo'"]]
			],
			[
				'"foo"',
				[['static', '"foo"']]
			],
			[
				"'\nfoo\n'",
				[['static', "'\nfoo\n'"]]
			],
			[
				"\"\nfoo\n\$foo\"",
				[['static', "\"\nfoo\n\$foo\""]]
			],
			[
				"\\'\\\\",
				[['static', "\\'\\\\"]]
			],
			[
				'<b><xsl:apply-templates/></b>',
				[
					['static', '<b>'],
					['static', '</b>']
				]
			],
			[
				'<a href="{@url}"><xsl:apply-templates/></a>',
				[
					[
						'dynamic',
						[
							'(^[^ ]+(?> (?!url=)[^=]+="[^"]*")*(?> url="([^"]*)")?.*)s',
							'<a href="$1">'
						]
					],
					['static', '</a>']
				]
			],
			[
				'<a href="{@url}"><xsl:copy-of select="@title"/><xsl:apply-templates/></a>',
				[
					[
						'dynamic',
						[
							'(^[^ ]+(?> (?!(?:title|url)=)[^=]+="[^"]*")*( title="[^"]*")?(?> (?!url=)[^=]+="[^"]*")*(?> url="([^"]*)")?.*)s',
							'<a href="$2"$1>'
						]
					],
					['static', '</a>']
				]
			],
			[
				'<a data-title="{@title}|{@title}" href="{@url}"><xsl:copy-of select="@title"/><xsl:apply-templates/></a>',
				[
					[
						'dynamic',
						[
							'(^[^ ]+(?> (?!(?:title|url)=)[^=]+="[^"]*")*( title="([^"]*)")?(?> (?!url=)[^=]+="[^"]*")*(?> url="([^"]*)")?.*)s',
							'<a data-title="$2|$2" href="$3"$1>'
						]
					],
					['static', '</a>']
				]
			],
			[
				'<hr title="1{@foo}1"/>',
				[
					[
						'dynamic',
						[
							'(^[^ ]+(?> (?!foo=)[^=]+="[^"]*")*(?> foo="([^"]*)")?.*)s',
							'<hr title="1${1}1">'
						]
					]
				]
			],
			[
				'<hr title="{@title}\\1\\x$1$x"/>',
				[
					[
						'dynamic',
						[
							'(^[^ ]+(?> (?!title=)[^=]+="[^"]*")*(?> title="([^"]*)")?.*)s',
							'<hr title="$1\\\\1\\x\\$1$x">'
						]
					]
				]
			],
			[
				'<object width="{@width}" height="{@height}"><param name="movie" value="{@url}"/></object>',
				[
					[
						'dynamic',
						[
							'(^[^ ]+(?> (?!(?:height|url|width)=)[^=]+="[^"]*")*(?> height="([^"]*)")?(?> (?!(?:url|width)=)[^=]+="[^"]*")*(?> url="([^"]*)")?(?> (?!width=)[^=]+="[^"]*")*(?> width="([^"]*)")?.*)s',
							'<object width="$3" height="$1"><param name="movie" value="$2"></object>'
						]
					]
				]
			],
			[
				'<xsl:value-of select="@foo"/>',
				[[
					'php',
					'$attributes+=[\'foo\'=>null];$html.=str_replace(\'&quot;\',\'"\',$attributes[\'foo\']??\'\');'
				]]
			],
			[
				'<b><xsl:if test="@foo"><xsl:attribute name="class">foo</xsl:attribute></xsl:if></b>',
				[[
					'php',
					'$html.=\'<b\';if(isset($attributes[\'foo\'])){$html.=\' class="foo"\';}$html.=\'></b>\';'
				]]
			],
			[
				self::ws('<blockquote>
					<xsl:if test="not(@author)">
						<xsl:attribute name="class">uncited</xsl:attribute>
					</xsl:if>
					<div>
						<xsl:if test="@author">
							<cite><xsl:value-of select="@author"/> wrote:</cite>
						</xsl:if>
						<xsl:apply-templates/>
					</div>
				</blockquote>'),
				[
					[
						'php',
						"\$html.='<blockquote';if(!isset(\$attributes['author'])){\$html.=' class=\"uncited\"';}\$html.='><div>';if(isset(\$attributes['author'])){\$html.='<cite>'.str_replace('&quot;','\"',\$attributes['author']??'').' wrote:</cite>';}"
					],
					['static', '</div></blockquote>']
				]
			],
			[
				self::ws('START<xsl:choose>
					<xsl:when test="@foo=1">
						<xsl:text>[1]</xsl:text>
						<xsl:choose>
							<xsl:when test="@foo=2">
								<xsl:text>[2]</xsl:text>
								<xsl:apply-templates/>
								<xsl:text>[/2]</xsl:text>
							</xsl:when>
							<xsl:otherwise>
								<xsl:text>[3]</xsl:text>
								<xsl:apply-templates/>
								<xsl:text>[/3]</xsl:text>
							</xsl:otherwise>
						</xsl:choose>
						<xsl:text>[/1]</xsl:text>
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>[o]</xsl:text>
						<xsl:choose>
							<xsl:when test="@foo=4">
								<xsl:text>[4]</xsl:text>
								<xsl:apply-templates/>
								<xsl:text>[/4]</xsl:text>
							</xsl:when>
							<xsl:otherwise>
								<xsl:text>[5]</xsl:text>
								<xsl:apply-templates/>
								<xsl:text>[/5]</xsl:text>
							</xsl:otherwise>
						</xsl:choose>
						<xsl:text>[/o]</xsl:text>
					</xsl:otherwise>
				</xsl:choose>END'),
				[
					[
						'php',
						'$attributes+=[\'foo\'=>null];$html.=\'START\';if($attributes[\'foo\']==1){$html.=\'[1]\';if($attributes[\'foo\']==2){$html.=\'[2]\';}else{$html.=\'[3]\';}}else{$html.=\'[o]\';if($attributes[\'foo\']==4){$html.=\'[4]\';}else{$html.=\'[5]\';}}$this->attributes[]=$attributes;'
					],
					[
						'php',
						'$attributes=array_pop($this->attributes);if($attributes[\'foo\']==1){if($attributes[\'foo\']==2){$html.=\'[/2]\';}else{$html.=\'[/3]\';}$html.=\'[/1]\';}else{if($attributes[\'foo\']==4){$html.=\'[/4]\';}else{$html.=\'[/5]\';}$html.=\'[/o]\';}$html.=\'END\';'
					]
				]
			],
			[
				'<b><xsl:apply-templates/><xsl:apply-templates/></b>',
				[]
			],
			[
				'<xsl:if test="@foo=1"><xsl:apply-templates/></xsl:if>',
				[]
			],
			[
				'<b><xsl:apply-templates select="FOO"/></b>',
				[]
			],
			[
				'<div><xsl:copy-of select="@*"/></div>',
				[]
			],
			[
				self::ws(
					'<xsl:choose>
						<xsl:when test="@foo"><xsl:apply-templates/></xsl:when>
						<xsl:otherwise><xsl:apply-templates/></xsl:otherwise>
					</xsl:choose>
					<xsl:choose>
						<xsl:when test="@foo"><xsl:apply-templates/></xsl:when>
						<xsl:otherwise><xsl:apply-templates/></xsl:otherwise>
					</xsl:choose>'
				),
				[]
			],
			[
				'<xsl:value-of select="$FOO"/>',
				[['php', '$html.=htmlspecialchars($this->params[\'FOO\'],0);']]
			],
			[
				'<xsl:comment><xsl:value-of select="@content"/></xsl:comment>',
				[['php', "\$attributes+=['content'=>null];\$html.='<!--'.str_replace('&quot;','\"',\$attributes['content']??'').'-->';"]]
			],
			[
				self::ws(
					'<xsl:choose>
						<xsl:when test=".=\':)\'">
							<img src="happy.png"/>
						</xsl:when>
						<xsl:otherwise>
							<xsl:value-of select="."/>
						</xsl:otherwise>
					</xsl:choose>'
				),
				[['php', '$textContent=$this->getQuickTextContent($xml);if($textContent===\':)\'){$html.=\'<img src="happy.png">\';}else{$html.=htmlspecialchars($textContent,0);}']]
			],
			[
				'<div><xsl:value-of select="@foo"/><xsl:apply-templates/></div>',
				[
					[
						'php',
						'$attributes+=[\'foo\'=>null];$html.=\'<div>\'.str_replace(\'&quot;\',\'"\',$attributes[\'foo\']??\'\');'
					],
					[
						'static',
						'</div>'
					]
				]
			],
			[
				'<div><xsl:apply-templates/><xsl:value-of select="@foo"/></div>',
				[
					[
						'php',
						'$attributes+=[\'foo\'=>null];$html.=\'<div>\';$this->attributes[]=$attributes;'
					],
					[
						'php',
						"\$attributes=array_pop(\$this->attributes);\$html.=str_replace('&quot;','\"',\$attributes['foo']??'').'</div>';"
					]
				]
			],
			[
				'<xsl:apply-templates/><xsl:value-of select="@foo"/>',
				[
					[
						'php',
						'$attributes+=[\'foo\'=>null];$this->attributes[]=$attributes;'
					],
					[
						'php',
						"\$attributes=array_pop(\$this->attributes);\$html.=str_replace('&quot;','\"',\$attributes['foo']??'');"
					]
				]
			],
			[
				'<b title="{.}"><xsl:apply-templates/></b>',
				[]
			],
			[
				'<xsl:choose><xsl:when test="@a=1"/><xsl:when test="@a=2"/><xsl:otherwise><xsl:apply-templates/></xsl:otherwise></xsl:choose>',
				[]
			],
		];
	}

	/**
	* @dataProvider getSourceTests
	*/
	public function testSource($templates, $contains, $setup = null)
	{
		if (isset($setup))
		{
			$setup();
		}

		$compiledTemplates = [];
		foreach ($templates as $tagName => $template)
		{
			$compiledTemplates[$tagName] = self::getPHP($template);
		}

		$this->assertStringContainsString($contains, Quick::getSource($compiledTemplates));
	}

	public static function getSourceTests()
	{
		return [
			[
				[
					'B' => '<b><xsl:if test="@foo">B</xsl:if><xsl:apply-templates/></b>',
					'X' => '<b><xsl:if test="@foo">X</xsl:if><xsl:apply-templates/></b>'
				],
				"switch(\$id){case'B':\$html.='<b>';if(isset(\$attributes['foo'])){\$html.='B';}break;case'X':\$html.='<b>';if(isset(\$attributes['foo'])){\$html.='X';}}"
			],
			[
				[
					'B' => '<b><xsl:if test="@foo">B</xsl:if><xsl:apply-templates/></b>',
					'X' => '<b><xsl:if test="@foo">X</xsl:if><xsl:apply-templates/></b>',
					'Y' => '<b><xsl:if test="@foo">X</xsl:if><xsl:apply-templates/></b>'
				],
				"switch(\$id){case'B':\$html.='<b>';if(isset(\$attributes['foo'])){\$html.='B';}break;case'X':case'Y':\$html.='<b>';if(isset(\$attributes['foo'])){\$html.='X';}}"
			],
			[
				[
					// System tags should be ignored but not namespaced tags
					'br' => '<br/>',
					'foo:bar' => 'foobar'
				],
				"\$static=['foo:bar'=>'foobar']"
			],
			[
				[
					'B' => '<b><xsl:if test="@foo">B</xsl:if><xsl:apply-templates/></b>',
					'X' => '<xsl:apply-templates/><xsl:apply-templates/>'
				],
				"\$quickRenderingTest='((?<=<)(?:[!?]|X[ />]))';"
			],
			[
				['X' => '<xsl:value-of select="@x"/>'],
				"\$html.=str_replace('&quot;','\"',\$attributes['x']??'');"
			],
			[
				['X' => '<xsl:if test="@x"><hr title="{@x}"/></xsl:if>'],
				"if(isset(\$attributes['x'])){\$html.='<hr title=\"'.\$attributes['x'].'\">';}"
			],
			[
				['X' => '<xsl:if test="@x"><hr title="{@x}{@y}"/></xsl:if>'],
				"if(isset(\$attributes['x'])){\$html.='<hr title=\"'.\$attributes['x'].\$attributes['y'].'\">';}"
			],
			[
				['X' => '<xsl:if test="@x=\'&quot;&lt;&gt;\'">x</xsl:if>'],
				"if(\$attributes['x']==='&quot;&lt;&gt;'){\$html.='x';}"
			],
			[
				['X' => '<xsl:if test="\'&quot;&lt;&gt;\'=@x">x</xsl:if>'],
				"if('&quot;&lt;&gt;'===\$attributes['x']){\$html.='x';}"
			],
			[
				['X' => '<xsl:if test="@x=1">x</xsl:if>'],
				"if(\$attributes['x']==1){\$html.='x';}"
			],
			[
				['X' => '<xsl:if test="@x!=1">x</xsl:if>'],
				"if(\$attributes['x']!=1){\$html.='x';}"
			],
			[
				['X' => '<xsl:if test="1=@x">x</xsl:if>'],
				"if(1==\$attributes['x']){\$html.='x';}"
			],
			[
				['X' => '<xsl:if test="1!=@x">x</xsl:if>'],
				"if(1!=\$attributes['x']){\$html.='x';}"
			],
			[
				['X' => '<hr title="{@x+200*@y}"/>'],
				"\$attributes['x']+200*\$attributes['y']"
			],
			[
				['X' => '<hr title="{100+@x}"/>'],
				"100+\$attributes['x']"
			],
			[
				['X' => '<xsl:if test="@x1=@x2">x</xsl:if>'],
				"if(\$attributes['x1']===\$attributes['x2']){\$html.='x';}"
			],
			[
				['X' => '<xsl:if test="contains(@x,\'&quot;\')">x</xsl:if>'],
				(function ()
				{
					return (version_compare(PHP_VERSION, '8.0', '>='))
						? "(str_contains(\$attributes['x']??'','&quot;'))"
						: "(strpos(\$attributes['x']??'','&quot;')!==false)";
				})()
			],
			[
				['X' => '<xsl:if test="contains(\'&quot;&lt;&gt;\',@x)">x</xsl:if>'],
				(function ()
				{
					return (version_compare(PHP_VERSION, '8.0', '>='))
						? "(str_contains('&quot;&lt;&gt;',\$attributes['x']??''))"
						: "(strpos('&quot;&lt;&gt;',\$attributes['x']??'')!==false)";
				})()
			],
			[
				['X' => '<xsl:if test="starts-with(\'&quot;&lt;&gt;\',@x)">x</xsl:if>'],
				(function ()
				{
					return (version_compare(PHP_VERSION, '8.0', '>='))
						? "(str_starts_with('&quot;&lt;&gt;',\$attributes['x']??''))"
						: "(strpos('&quot;&lt;&gt;',\$attributes['x']??'')===0)";
				})()
			],
			[
				['X' => '<xsl:if test="starts-with(@x,\'&quot;&lt;&gt;\')">x</xsl:if>'],
				(function ()
				{
					return (version_compare(PHP_VERSION, '8.0', '>='))
						? "(str_starts_with(\$attributes['x']??'','&quot;&lt;&gt;'))"
						: "(strpos(\$attributes['x']??'','&quot;&lt;&gt;')===0)";
				})()
			],
			[
				['X' => '<xsl:if test="not(contains(@x,\'&quot;\'))">x</xsl:if>'],
				(function ()
				{
					return (version_compare(PHP_VERSION, '8.0', '>='))
						? "(!str_contains(\$attributes['x']??'','&quot;'))"
						: "(strpos(\$attributes['x']??'','&quot;')===false)";
				})()
			],
			[
				['X' => '<xsl:if test="not(contains(\'&quot;&lt;&gt;\',@x))">x</xsl:if>'],
				(function ()
				{
					return (version_compare(PHP_VERSION, '8.0', '>='))
						? "(!str_contains('&quot;&lt;&gt;',\$attributes['x']??''))"
						: "(strpos('&quot;&lt;&gt;',\$attributes['x']??'')===false)";
				})()
			],
			[
				['X' => '<xsl:if test="ends-with(@x,\'&quot;&lt;&gt;\')">x</xsl:if>'],
				(function ()
				{
					return (version_compare(PHP_VERSION, '8.0', '>='))
						? "(str_ends_with(\$attributes['x']??'','&quot;&lt;&gt;'))"
						: "(substr(htmlspecialchars_decode(\$attributes['x']??''),-3)==='\"<>')";
				})()
			],
			[
				['X' => "<hr title=\"{translate(@x,'_','-')}\"/>"],
				"'<hr title=\"'.strtr(\$attributes['x']??'','_','-').'\">'"
			],
			[
				['X' => "<hr title=\"{translate(@x,'ABC','abc')}\"/>"],
				"'<hr title=\"'.strtr(\$attributes['x']??'','ABC','abc').'\">'"
			],
			[
				// Replacing "a" with "A" would mess up "&amp;"
				['X' => "<hr title=\"{translate(@x,'abc','ABC')}\"/>"],
				"'<hr title=\"'.htmlspecialchars(strtr(htmlspecialchars_decode(\$attributes['x']??''),'abc','ABC'),2).'\">'"
			],
			[
				['X' => '<xsl:if test="@*">Y</xsl:if>'],
				'if($this->hasNonNullValues($attributes)){$html.=\'Y\';}'
			],
			[
				['X' => '<xsl:if test="string(@foo) != \'\'">X</xsl:if>'],
				"if(\$attributes['foo']!==''){\$html.='X';}"
			],
			[
				['X' => '<xsl:if test="\'\'!=@foo">X</xsl:if>'],
				"if(''!==\$attributes['foo']){\$html.='X';}"
			],
		];
	}
}