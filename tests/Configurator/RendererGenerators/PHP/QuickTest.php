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
		return array(
			array(
				array('/*0*/'),
				'/*0*/'
			),
			array(
				array('/*0*/', '/*1*/'),
				'if($n===0){/*0*/}else{/*1*/}'
			),
			array(
				array('/*0*/', '/*1*/', '/*2*/', '/*3*/', '/*4*/', '/*5*/', '/*6*/', '/*7*/'),
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
			),
			array(
				array('/*0*/', '/*1*/', '/*2*/', '/*3*/', '/*4*/', '/*5*/', '/*6*/', '/*7*/', '/*8*/'),
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
			),
			array(
				array('/*0*/', '/*1*/', '/*2*/', '/*3*/', '/*4*/', '/*5*/', '/*6*/', '/*7*/', '/*8*/', '/*9*/', '/*10*/'),
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
			),
		);
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

		$compiledTemplates = array();
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
		$_this = $this;

		return array(
			array(
				array(
					'B' => '<b><xsl:apply-templates/></b>',
					'I' => '<i><xsl:apply-templates/></i>',
					'U' => '<u><xsl:apply-templates/></u>'
				),
				'<r>Hello <B><s>[b]</s>world<e>[/b]</e></B>!<br/>x</r>',
				'Hello <b>world</b>!<br>x'
			),
			array(
				array(
					'B' => '<b><xsl:apply-templates/></b>'
				),
				'<r><p>Hello <B><s>[b]</s>world<e>[/b]</e></B>!<br/>x</p></r>',
				'<p>Hello <b>world</b>!<br>x</p>'
			),
			array(
				array(
					'URL' => '<a href="{@url}"><xsl:apply-templates/></a>'
				),
				'<r>Go to <URL url="http://example.org"><s>[url]</s>http://example.org<e>[/url]</e></URL>!</r>',
				'Go to <a href="http://example.org">http://example.org</a>!'
			),
			array(
				array(
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
				),
				'<r><QUOTE author="John Doe"><s>[quote="John Doe"]</s>...<e>[/quote]</e></QUOTE></r>',
				'<blockquote><div><cite>John Doe wrote:</cite>...</div></blockquote>'
			),
			array(
				array(
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
				),
				'<r><LIST><s>[list]</s><LI><s>[*]</s>one</LI><LI><s>[*]</s>two</LI><LI><s>[*]</s>three</LI><e>[/list]</e></LIST></r>',
				'<ul><li>one</li><li>two</li><li>three</li></ul>'
			),
			array(
				array(
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
				),
				'<r><LIST type="upper-roman"><s>[list]</s><LI><s>[*]</s>one</LI><LI><s>[*]</s>two</LI><LI><s>[*]</s>three</LI><e>[/list]</e></LIST></r>',
				'<ol style="list-style-type:upper-roman"><li>one</li><li>two</li><li>three</li></ol>'
			),
			array(
				array(
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
				),
				'<r><LIST type="square"><s>[list]</s><LI><s>[*]</s>one</LI><LI><s>[*]</s>two</LI><LI><s>[*]</s>three</LI><e>[/list]</e></LIST></r>',
				'<ul style="list-style-type:square"><li>one</li><li>two</li><li>three</li></ul>'
			),
			array(
				array('IMG' => '<img src="{@url}"/>'),
				'<r><IMG url="foo.png">foo.png</IMG></r>',
				'<img src="foo.png">'
			),
			array(
				array('IMG' => '<img src="{@url}"/>'),
				'<r><IMG url="foo.png"/></r>',
				'<img src="foo.png">'
			),
			array(
				array('X' => '<hr title="{@x}"/>'),
				'<r><X x="&quot;\'&lt;&gt;&amp;"/></r>',
				'<hr title="&quot;\'&lt;&gt;&amp;">'
			),
			array(
				array('X' => '<xsl:value-of select="@x"/>'),
				'<r><X x="\'&quot;&lt;&gt;&amp;"/></r>',
				'\'"&lt;&gt;&amp;'
			),
			array(
				array('X' => '<xsl:if test="@x=\'&quot;&lt;&gt;\'">x</xsl:if>'),
				'<r><X x="&quot;&lt;&gt;"/></r>',
				'x'
			),
			array(
				array('X' => '<xsl:if test="\'&quot;&lt;&gt;\'=@x">x</xsl:if>'),
				'<r><X x="&quot;&lt;&gt;"/></r>',
				'x'
			),
			array(
				array('X' => '<xsl:if test="@x=1">x</xsl:if>'),
				'<r><X x="1"/><X x="2"/></r>',
				'x'
			),
			array(
				array('X' => '<xsl:if test="11=@x">x</xsl:if>'),
				'<r><X x="11"/><X x="2"/></r>',
				'x'
			),
			array(
				array('X' => '<hr title="{@x+200*@y}"/>'),
				'<r><X x="10" y="3"/></r>',
				'<hr title="610">',
				function () use ($_this)
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						$_this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
					}
				}
			),
			array(
				array('X' => '<hr title="{100+@x}"/>'),
				'<r><X x="10" y="3"/></r>',
				'<hr title="110">',
				function () use ($_this)
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						$_this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
					}
				}
			),
			array(
				array('X' => "<hr title=\"{translate(@x,'abc','ABC')}\"/>"),
				'<r><X x="&amp;amp;"/></r>',
				'<hr title="&amp;Amp;">'
			),
		);
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
		return array(
			array(
				'',
				array(array('static', ''))
			),
			array(
				'foo',
				array(array('static', 'foo'))
			),
			array(
				"'foo'",
				array(array('static', "'foo'"))
			),
			array(
				'"foo"',
				array(array('static', '"foo"'))
			),
			array(
				"'\nfoo\n'",
				array(array('static', "'\nfoo\n'"))
			),
			array(
				"\"\nfoo\n\$foo\"",
				array(array('static', "\"\nfoo\n\$foo\""))
			),
			array(
				"\\'\\\\",
				array(array('static', "\\'\\\\"))
			),
			array(
				'<b><xsl:apply-templates/></b>',
				array(
					array('static', '<b>'),
					array('static', '</b>')
				)
			),
			array(
				'<a href="{@url}"><xsl:apply-templates/></a>',
				array(
					array(
						'dynamic',
						array(
							'(^[^ ]+(?> (?!url=)[^=]+="[^"]*")*(?> url="([^"]*)")?.*)s',
							'<a href="$1">'
						)
					),
					array('static', '</a>')
				)
			),
			array(
				'<a href="{@url}"><xsl:copy-of select="@title"/><xsl:apply-templates/></a>',
				array(
					array(
						'dynamic',
						array(
							'(^[^ ]+(?> (?!(?>title|url)=)[^=]+="[^"]*")*( title="[^"]*")?(?> (?!url=)[^=]+="[^"]*")*(?> url="([^"]*)")?.*)s',
							'<a href="$2"$1>'
						)
					),
					array('static', '</a>')
				)
			),
			array(
				'<a data-title="{@title}|{@title}" href="{@url}"><xsl:copy-of select="@title"/><xsl:apply-templates/></a>',
				array(
					array(
						'dynamic',
						array(
							'(^[^ ]+(?> (?!(?>title|url)=)[^=]+="[^"]*")*( title="([^"]*)")?(?> (?!url=)[^=]+="[^"]*")*(?> url="([^"]*)")?.*)s',
							'<a data-title="$2|$2" href="$3"$1>'
						)
					),
					array('static', '</a>')
				)
			),
			array(
				'<hr title="1{@foo}1"/>',
				array(
					array(
						'dynamic',
						array(
							'(^[^ ]+(?> (?!foo=)[^=]+="[^"]*")*(?> foo="([^"]*)")?.*)s',
							'<hr title="1${1}1">'
						)
					)
				)
			),
			array(
				'<hr title="{@title}\\1\\x$1$x"/>',
				array(
					array(
						'dynamic',
						array(
							'(^[^ ]+(?> (?!title=)[^=]+="[^"]*")*(?> title="([^"]*)")?.*)s',
							'<hr title="$1\\\\1\\x\\$1$x">'
						)
					)
				)
			),
			array(
				'<object width="{@width}" height="{@height}"><param name="movie" value="{@url}"/></object>',
				array(
					array(
						'dynamic',
						array(
							'(^[^ ]+(?> (?!(?>height|url|width)=)[^=]+="[^"]*")*(?> height="([^"]*)")?(?> (?!(?>url|width)=)[^=]+="[^"]*")*(?> url="([^"]*)")?(?> (?!width=)[^=]+="[^"]*")*(?> width="([^"]*)")?.*)s',
							'<object width="$3" height="$1"><param name="movie" value="$2"></object>'
						)
					)
				)
			),
			array(
				'<xsl:value-of select="@foo"/>',
				array(array(
					'php',
					'$attributes+=array(\'foo\'=>null);$html=str_replace(\'&quot;\',\'"\',$attributes[\'foo\']);'
				))
			),
			array(
				'<b><xsl:if test="@foo"><xsl:attribute name="class">foo</xsl:attribute></xsl:if></b>',
				array(array(
					'php',
					'$html=\'<b\';if(isset($attributes[\'foo\'])){$html.=\' class="foo"\';}$html.=\'></b>\';'
				))
			),
			array(
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
				array(
					array(
						'php',
						"\$html='<blockquote';if(!isset(\$attributes['author'])){\$html.=' class=\"uncited\"';}\$html.='><div>';if(isset(\$attributes['author'])){\$html.='<cite>'.str_replace('&quot;','\"',\$attributes['author']).' wrote:</cite>';}"
					),
					array('static', '</div></blockquote>')
				)
			),
			array(
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
				array(
					array(
						'php',
						'$attributes+=array(\'foo\'=>null);$html=\'START\';if($attributes[\'foo\']==1){$html.=\'[1]\';if($attributes[\'foo\']==2){$html.=\'[2]\';}else{$html.=\'[3]\';}}else{$html.=\'[o]\';if($attributes[\'foo\']==4){$html.=\'[4]\';}else{$html.=\'[5]\';}}self::$attributes[]=$attributes;'
					),
					array(
						'php',
						'$attributes=array_pop(self::$attributes);$html=\'\';if($attributes[\'foo\']==1){if($attributes[\'foo\']==2){$html.=\'[/2]\';}else{$html.=\'[/3]\';}$html.=\'[/1]\';}else{if($attributes[\'foo\']==4){$html.=\'[/4]\';}else{$html.=\'[/5]\';}$html.=\'[/o]\';}$html.=\'END\';'
					)
				)
			),
			array(
				'<b><xsl:apply-templates/><xsl:apply-templates/></b>',
				false
			),
			array(
				'<xsl:if test="@foo=1"><xsl:apply-templates/></xsl:if>',
				false
			),
			array(
				'<b><xsl:apply-templates select="FOO"/></b>',
				false
			),
			array(
				'<div><xsl:copy-of select="@*"/></div>',
				false
			),
			array(
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
			),
			array(
				'<xsl:value-of select="$FOO"/>',
				array(array('php', '$html=htmlspecialchars($this->params[\'FOO\'],0);'))
			),
			array(
				'<xsl:comment><xsl:value-of select="@content"/></xsl:comment>',
				array(array('php', "\$attributes+=array('content'=>null);\$html='<!--'.str_replace('&quot;','\"',\$attributes['content']).'-->';"))
			),
			array(
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
				array(array('php', '$html=\'\';if($textContent===\':)\'){$html.=\'<img src="happy.png">\';}else{$html.=htmlspecialchars($textContent,0);}'))
			),
		);
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

		$compiledTemplates = array();
		foreach ($templates as $tagName => $template)
		{
			$compiledTemplates[$tagName] = self::getPHP($template, 'html');
		}

		$this->assertContains($contains, Quick::getSource($compiledTemplates));
	}

	public function getSourceTests()
	{
		$_this = $this;

		return array(
			array(
				array(
					'B' => '<b><xsl:if test="@foo">B</xsl:if><xsl:apply-templates/></b>',
					'X' => '<b><xsl:if test="@foo">X</xsl:if><xsl:apply-templates/></b>'
				),
				'if($qb===0){$html=\'<b>\';if(isset($attributes[\'foo\'])){$html.=\'B\';}}else{$html=\'<b>\';if(isset($attributes[\'foo\'])){$html.=\'X\';}}'
			),
			array(
				array(
					'B' => '<b><xsl:if test="@foo">B</xsl:if><xsl:apply-templates/></b>',
					'X' => '<b><xsl:if test="@foo">X</xsl:if><xsl:apply-templates/></b>',
					'Y' => '<b><xsl:if test="@foo">X</xsl:if><xsl:apply-templates/></b>'
				),
				'if($qb===0){$html=\'<b>\';if(isset($attributes[\'foo\'])){$html.=\'B\';}}else{$html=\'<b>\';if(isset($attributes[\'foo\'])){$html.=\'X\';}}'
			),
			array(
				array(
					// System tags should be ignored but not namespaced tags
					'br' => '<br/>',
					'foo:bar' => 'foobar'
				),
				"\$static=array('foo:bar'=>'foobar')"
			),
			array(
				array(
					'B' => '<b><xsl:if test="@foo">B</xsl:if><xsl:apply-templates/></b>',
					'X' => '<xsl:apply-templates/><xsl:apply-templates/>'
				),
				"public static \$quickRenderingTest='(<X[ />])';"
			),
			array(
				array('X' => '<xsl:value-of select="@x"/>'),
				"\$html=str_replace('&quot;','\"',\$attributes['x']);"
			),
			array(
				array('X' => '<xsl:if test="@x"><hr title="{@x}"/></xsl:if>'),
				"if(isset(\$attributes['x'])){\$html.='<hr title=\"'.\$attributes['x'].'\">';}"
			),
			array(
				array('X' => '<xsl:if test="@x=\'&quot;&lt;&gt;\'">x</xsl:if>'),
				"if(\$attributes['x']==='&quot;&lt;&gt;'){\$html.='x';}"
			),
			array(
				array('X' => '<xsl:if test="\'&quot;&lt;&gt;\'=@x">x</xsl:if>'),
				"if('&quot;&lt;&gt;'===\$attributes['x']){\$html.='x';}"
			),
			array(
				array('X' => '<xsl:if test="@x=1">x</xsl:if>'),
				"if(\$attributes['x']==1){\$html.='x';}"
			),
			array(
				array('X' => '<xsl:if test="1=@x">x</xsl:if>'),
				"if(1==\$attributes['x']){\$html.='x';}"
			),
			array(
				array('X' => '<hr title="{@x+200*@y}"/>'),
				"\$attributes['x']+200*\$attributes['y']",
				function () use ($_this)
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						$_this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
					}
				}
			),
			array(
				array('X' => '<hr title="{100+@x}"/>'),
				"100+\$attributes['x']",
				function () use ($_this)
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						$_this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
					}
				}
			),
			array(
				array('X' => '<xsl:if test="@x1=@x2">x</xsl:if>'),
				"if(\$attributes['x1']===\$attributes['x2']){\$html.='x';}"
			),
			array(
				array('X' => '<xsl:if test="contains(@x,\'&quot;\')">x</xsl:if>'),
				"(strpos(\$attributes['x'],'&quot;')!==false)"
			),
			array(
				array('X' => '<xsl:if test="contains(\'&quot;&lt;&gt;\',@x)">x</xsl:if>'),
				"(strpos('&quot;&lt;&gt;',\$attributes['x'])!==false)"
			),
			array(
				array('X' => '<xsl:if test="starts-with(\'&quot;&lt;&gt;\',@x)">x</xsl:if>'),
				"(strpos('&quot;&lt;&gt;',\$attributes['x'])===0)"
			),
			array(
				array('X' => '<xsl:if test="starts-with(@x,\'&quot;&lt;&gt;\')">x</xsl:if>'),
				"(strpos(\$attributes['x'],'&quot;&lt;&gt;')===0)"
			),
			array(
				array('X' => '<xsl:if test="not(contains(@x,\'&quot;\'))">x</xsl:if>'),
				"(strpos(\$attributes['x'],'&quot;')===false)",
				function () use ($_this)
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						$_this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
					}
				}
			),
			array(
				array('X' => '<xsl:if test="not(contains(\'&quot;&lt;&gt;\',@x))">x</xsl:if>'),
				"(strpos('&quot;&lt;&gt;',\$attributes['x'])===false)",
				function () use ($_this)
				{
					if (version_compare(PCRE_VERSION, '8.13', '<'))
					{
						$_this->markTestSkipped('This optimization requires PCRE 8.13 or newer');
					}
				}
			),
			array(
				array('X' => "<hr title=\"{translate(@x,'_','-')}\"/>"),
				"'<hr title=\"'.strtr(\$attributes['x'],'_','-').'\">'"
			),
			array(
				array('X' => "<hr title=\"{translate(@x,'ABC','abc')}\"/>"),
				"'<hr title=\"'.strtr(\$attributes['x'],'ABC','abc').'\">'"
			),
			array(
				// Replacing "a" with "A" would mess up "&amp;"
				array('X' => "<hr title=\"{translate(@x,'abc','ABC')}\"/>"),
				"'<hr title=\"'.htmlspecialchars(strtr(htmlspecialchars_decode(\$attributes['x']),'abc','ABC'),2).'\">'"
			),
		);
	}
}