<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP;

use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\Optimizer;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\Quick;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\Serializer;
use s9e\TextFormatter\Tests\Test;

/**
* @requires extension tokenizer
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\Quick
*/
class QuickTest extends Test
{
	protected static function ws($template)
	{
		return preg_replace('(>\\s+<)', '><', $template);
	}

	protected function getPHP($template, $renderingType)
	{
		$serializer = new Serializer;
		$optimizer  = new Optimizer;

		$ir  = TemplateParser::parse($template, $renderingType);
		$php = $serializer->serialize($ir->documentElement);
		$php = $optimizer->optimize($php);

		return $php;
	}

	/**
	* @dataProvider getConditionalsTests
	*/
	public function testConditionals($conditionals, $expected)
	{
		$expected = preg_replace('(\\s+)', '', $expected);
		$this->assertSame($expected, Quick::generateConditionals('$n', $conditionals));
	}

	public function getConditionalsTests()
	{
		return [
			[
				['/*0*/'],
				'/*0*/'
			],
			[
				['/*0*/', '/*1*/'],
				'if($n===0){/*0*/}else{/*1*/}'
			],
			[
				['/*0*/', '/*1*/', '/*2*/', '/*3*/', '/*4*/', '/*5*/', '/*6*/', '/*7*/'],
				'
					if ($n<4)
					{
							if($n===0){/*0*/}
						elseif($n===1){/*1*/}
						elseif($n===2){/*2*/}
						else          {/*3*/}
					}
					elseif($n===4){/*4*/}
					elseif($n===5){/*5*/}
					elseif($n===6){/*6*/}
					else          {/*7*/}
				'
			],
			[
				['/*0*/', '/*1*/', '/*2*/', '/*3*/', '/*4*/', '/*5*/', '/*6*/', '/*7*/', '/*8*/'],
				'
					if($n<5)
					{
						if($n<3)
						{
								if($n===0){/*0*/}
							elseif($n===1){/*1*/}
							else          {/*2*/}
						}
						elseif($n===3)    {/*3*/}
						else              {/*4*/}
					}
					elseif($n===5)        {/*5*/}
					elseif($n===6)        {/*6*/}
					elseif($n===7)        {/*7*/}
					else                  {/*8*/}
				'
			],
			[
				['/*0*/', '/*1*/', '/*2*/', '/*3*/', '/*4*/', '/*5*/', '/*6*/', '/*7*/', '/*8*/', '/*9*/', '/*10*/'],
				'
					if($n<6)
					{
						if($n<3)
						{
								if($n===0){/*0*/}
							elseif($n===1){/*1*/}
							else          {/*2*/}
						}
						elseif($n===3)    {/*3*/}
						elseif($n===4)    {/*4*/}
						else              {/*5*/}
					}
					elseif($n<9)
					{
							if($n===6)    {/*6*/}
						elseif($n===7)    {/*7*/}
						else              {/*8*/}
					}
					elseif($n===9)        {/*9*/}
					else                  {/*10*/}
				'
			],
		];
	}

	/**
	* @dataProvider getRenderingTests
	*/
	public function testRendering($templates, $xml, $expected)
	{
		$compiledTemplates = [];
		foreach ($templates as $tagName => $template)
		{
			$compiledTemplates[$tagName] = self::getPHP($template, 'html');
		}

		$php = Quick::getSource($compiledTemplates);
		$className = 'quick_' . md5($php);

		if (!class_exists($className))
		{
			eval('class ' . $className . '{public function render($xml){return $this->renderQuick($xml);}' . $php . '}');
		}

		$renderer = new $className;
		$this->assertSame($expected, $renderer->render($xml));
	}

	public function getRenderingTests()
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
							<xsl:when test="contains(\'upperlowerdecim\',substring(@type,1,5))">
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
							<xsl:when test="contains(\'upperlowerdecim\',substring(@type,1,5))">
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
							<xsl:when test="contains(\'upperlowerdecim\',substring(@type,1,5))">
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
				[
					'IMG' => '<img src="{@url}"/>'
				],
				'<r><IMG url="foo.png">foo.png</IMG></r>',
				'<img src="foo.png">'
			],
			[
				[
					'IMG' => '<img src="{@url}"/>'
				],
				'<r><IMG url="foo.png"/></r>',
				'<img src="foo.png">'
			],
		];
	}

	/**
	* @dataProvider getRenderingStrategyTests
	*/
	public function testRenderingStrategy($template, $expected)
	{
		$php = self::getPHP($template, 'html');
		$this->assertSame($expected, Quick::getRenderingStrategy($php));
	}

	public function getRenderingStrategyTests()
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
							'(^\\S*(?> (?!url=)[^=]+="[^"]*")*(?> url="([^"]+)")?.*)s',
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
							'(^\\S*(?> (?!(?>title|url)=)[^=]+="[^"]*")*( title="[^"]+")?(?> (?!(?>title|url)=)[^=]+="[^"]*")*(?> url="([^"]+)")?.*)s',
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
							'(^\\S*(?> (?!(?>title|url)=)[^=]+="[^"]*")*( title="([^"]+)")?(?> (?!(?>title|url)=)[^=]+="[^"]*")*(?> url="([^"]+)")?.*)s',
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
							'(^\\S*(?> (?!foo=)[^=]+="[^"]*")*(?> foo="([^"]+)")?.*)s',
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
							'(^\\S*(?> (?!title=)[^=]+="[^"]*")*(?> title="([^"]+)")?.*)s',
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
							'(^\\S*(?> (?!(?>height|url|width)=)[^=]+="[^"]*")*(?> height="([^"]+)")?(?> (?!(?>height|url|width)=)[^=]+="[^"]*")*(?> url="([^"]+)")?(?> (?!(?>height|url|width)=)[^=]+="[^"]*")*(?> width="([^"]+)")?.*)s',
							'<object width="$3" height="$1"><param name="movie" value="$2"></object>'
						]
					]
				]
			],
			[
				'<xsl:value-of select="@foo"/>',
				[[
					'php',
					'$attributes+=[\'foo\'=>null];$html=htmlspecialchars($attributes[\'foo\'],0);'
				]]
			],
			[
				'<b><xsl:if test="@foo"><xsl:attribute name="class">foo</xsl:attribute></xsl:if></b>',
				[[
					'php',
					'$html=\'<b\';if(isset($attributes[\'foo\'])){$html.=\' class="foo"\';}$html.=\'></b>\';'
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
						'$html=\'<blockquote\';if(!isset($attributes[\'author\'])){$html.=\' class="uncited"\';}$html.=\'><div>\';if(isset($attributes[\'author\'])){$html.=\'<cite>\'.htmlspecialchars($attributes[\'author\'],0).\' wrote:</cite>\';}'
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
						'$attributes+=[\'foo\'=>null];$html=\'START\';if($attributes[\'foo\']==1){$html.=\'[1]\';if($attributes[\'foo\']==2){$html.=\'[2]\';}else{$html.=\'[3]\';}}else{$html.=\'[o]\';if($attributes[\'foo\']==4){$html.=\'[4]\';}else{$html.=\'[5]\';}}self::$attributes[]=$attributes;'
					],
					[
						'php',
						'$attributes=array_pop(self::$attributes);$html=\'\';if($attributes[\'foo\']==1){if($attributes[\'foo\']==2){$html.=\'[/2]\';}else{$html.=\'[/3]\';}$html.=\'[/1]\';}else{if($attributes[\'foo\']==4){$html.=\'[/4]\';}else{$html.=\'[/5]\';}$html.=\'[/o]\';}$html.=\'END\';'
					]
				]
			],
			[
				'<b><xsl:apply-templates/><xsl:apply-templates/></b>',
				false
			],
			[
				'<xsl:if test="@foo=1"><xsl:apply-templates/></xsl:if>',
				false
			],
			[
				'<b><xsl:apply-templates select="FOO"/></b>',
				false
			],
			[
				'<div><xsl:copy-of select="@*"/></div>',
				false
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
				false
			],
			[
				'<xsl:value-of select="$FOO"/>',
				[['php', '$html=htmlspecialchars($this->params[\'FOO\'],0);']]
			],
			[
				'<xsl:comment><xsl:value-of select="@content"/></xsl:comment>',
				[['php', "\$attributes+=['content'=>null];\$html='<!--'.htmlspecialchars(\$attributes['content'],0).'-->';"]]
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
				[['php', '$html=\'\';if($textContent===\':)\'){$html.=\'<img src="happy.png">\';}else{$html.=htmlspecialchars($textContent,0);}']]
			],
		];
	}

	/**
	* @dataProvider getSourceTests
	*/
	public function testSource($templates, $contains)
	{
		$compiledTemplates = [];
		foreach ($templates as $tagName => $template)
		{
			$compiledTemplates[$tagName] = self::getPHP($template, 'html');
		}

		$this->assertContains($contains, Quick::getSource($compiledTemplates));
	}

	public function getSourceTests()
	{
		return [
			[
				[
					'B' => '<b><xsl:if test="@foo">B</xsl:if><xsl:apply-templates/></b>',
					'X' => '<b><xsl:if test="@foo">X</xsl:if><xsl:apply-templates/></b>'
				],
				'if($qb===0){$html=\'<b>\';if(isset($attributes[\'foo\'])){$html.=\'B\';}}else{$html=\'<b>\';if(isset($attributes[\'foo\'])){$html.=\'X\';}}'
			],
			[
				[
					'B' => '<b><xsl:if test="@foo">B</xsl:if><xsl:apply-templates/></b>',
					'X' => '<b><xsl:if test="@foo">X</xsl:if><xsl:apply-templates/></b>',
					'Y' => '<b><xsl:if test="@foo">X</xsl:if><xsl:apply-templates/></b>'
				],
				'if($qb===0){$html=\'<b>\';if(isset($attributes[\'foo\'])){$html.=\'B\';}}else{$html=\'<b>\';if(isset($attributes[\'foo\'])){$html.=\'X\';}}'
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
				"public static \$quickRenderingTest='(<X[ />])';"
			],
		];
	}
}