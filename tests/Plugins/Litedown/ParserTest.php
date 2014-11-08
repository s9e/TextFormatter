<?php

namespace s9e\TextFormatter\Tests\Plugins\Litedown;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Litedown\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Litedown\Parser
*/
class ParserTest extends Test
{
	/**
	* @testdox Parsing tests
	* @dataProvider getParsingTests
	*/
	public function testParsing($original, $expected, array $pluginOptions = array(), $setup = null, $expectedJS = null, $assertMethod = 'assertSame')
	{
		$pluginName = preg_replace('/.*\\\\([^\\\\]+)\\\\.*/', '$1', get_class($this));

		$configurator = new Configurator;
		$plugin = $configurator->plugins->load($pluginName, $pluginOptions);

		if ($setup)
		{
			$setup($configurator, $plugin);
		}

		$this->$assertMethod($expected, $configurator->getParser()->parse($original));
	}

	/**
	* @group needs-js
	* @testdox Parsing tests (JavaScript)
	* @dataProvider getParsingTests
	* @requires extension json
	* @covers s9e\TextFormatter\Configurator\JavaScript
	*/
	public function testJavaScriptParsing($original, $expected, array $pluginOptions = array(), $setup = null, $expectedJS = null, $assertMethod = 'assertSame')
	{
		if (isset($expectedJS))
		{
			$expected = $expectedJS;
		}

		$pluginName = preg_replace('/.*\\\\([^\\\\]+)\\\\.*/', '$1', get_class($this));

		$plugin = $this->configurator->plugins->load($pluginName, $pluginOptions);

		if ($setup)
		{
			$setup($this->configurator, $plugin);
		}

		$this->assertJSParsing($original, $expected);
	}

	/**
	* @requires extension xsl
	* @testdox Parsing+rendering tests
	* @dataProvider getRenderingTests
	*/
	public function testRendering($original, $expected, array $pluginOptions = array(), $setup = null, $assertMethod = 'assertSame')
	{
		$pluginName = preg_replace('/.*\\\\([^\\\\]+)\\\\.*/', '$1', get_class($this));

		$plugin = $this->configurator->plugins->load($pluginName, $pluginOptions);

		if ($setup)
		{
			$setup($this->configurator, $plugin);
		}

		extract($this->configurator->finalize());

		$this->$assertMethod($expected, $renderer->render($parser->parse($original)));
	}

	public function getParsingTests()
	{
		return self::fixTests(array(
			array(
				// Ensure that automatic line breaks can be enabled
				"First\nSecond",
				"<t><p>First<br/>\nSecond</p></t>",
				array(),
				function ($configurator)
				{
					$configurator->rootRules->enableAutoLineBreaks();
				}
			),
			// Paragraphs and quotes
			array(
				'foo',
				'<t><p>foo</p></t>'
			),
			array(
				"foo\n\nbar",
				"<t><p>foo</p>\n\n<p>bar</p></t>"
			),
			array(
				'> foo',
				'<r><QUOTE><i>&gt; </i><p>foo</p></QUOTE></r>'
			),
			array(
				array(
					'> > foo',
					'> ',
					'> bar',
					'',
					'baz'
				),
				array(
					'<r><QUOTE><QUOTE><i>&gt; &gt; </i><p>foo</p></QUOTE>',
					'<i>&gt; </i>',
					'<i>&gt; </i><p>bar</p></QUOTE>',
					'',
					'<p>baz</p></r>'
				)
			),
			array(
				array(
					'> > foo',
					'> ',
					'> > bar',
					'',
					'baz'
				),
				array(
					'<r><QUOTE><QUOTE><i>&gt; &gt; </i><p>foo</p>',
					'<i>&gt; </i>',
					'<i>&gt; &gt; </i><p>bar</p></QUOTE></QUOTE>',
					'',
					'<p>baz</p></r>'
				)
			),
			array(
				array(
					'> > foo',
					'',
					'> > bar',
					'',
					'baz'
				),
				array(
					'<r><QUOTE><QUOTE><i>&gt; &gt; </i><p>foo</p>',
					'',
					'<i>&gt; &gt; </i><p>bar</p></QUOTE></QUOTE>',
					'',
					'<p>baz</p></r>'
				)
			),
			array(
				array(
					'> foo',
					'bar',
					'baz',
					'',
					'quux'
				),
				array(
					'<r><QUOTE><i>&gt; </i><p>foo',
					'bar',
					'baz</p></QUOTE>',
					'',
					'<p>quux</p></r>'
				)
			),
			array(
				array(
					'foo',
					'> bar',
					'baz',
					'',
					'quux'
				),
				array(
					'<r><p>foo</p>',
					'<QUOTE><i>&gt; </i><p>bar',
					'baz</p></QUOTE>',
					'',
					'<p>quux</p></r>'
				)
			),
			// Indented code blocks
			array(
				array(
					'    code',
					'    more code',
					'',
					'foo'
				),
				array(
					'<r><i>    </i><CODE>code',
					'<i>    </i>more code</CODE>',
					'',
					'<p>foo</p></r>'
				)
			),
			array(
				array(
					'    code',
					"\tmore code",
					'',
					'foo'
				),
				array(
					'<r><i>    </i><CODE>code',
					"<i>\t</i>more code</CODE>",
					'',
					'<p>foo</p></r>'
				)
			),
			array(
				array(
					'    code',
					"   \tmore code",
					'',
					'foo'
				),
				array(
					'<r><i>    </i><CODE>code',
					"<i>   \t</i>more code</CODE>",
					'',
					'<p>foo</p></r>'
				)
			),
			array(
				array(
					'    code',
					"    \tmore code",
					'',
					'foo'
				),
				array(
					'<r><i>    </i><CODE>code',
					"<i>    </i>\tmore code</CODE>",
					'',
					'<p>foo</p></r>'
				)
			),
			array(
				array(
					'bar',
					'',
					'    code',
					'    more code',
					'',
					'foo'
				),
				array(
					'<r><p>bar</p>',
					'',
					'<i>    </i><CODE>code',
					'<i>    </i>more code</CODE>',
					'',
					'<p>foo</p></r>'
				)
			),
			array(
				array(
					'bar',
					'',
					'    code',
					'',
					'    more code',
					'',
					'foo'
				),
				array(
					'<r><p>bar</p>',
					'',
					'<i>    </i><CODE>code',
					'',
					'<i>    </i>more code</CODE>',
					'',
					'<p>foo</p></r>'
				)
			),
			array(
				array(
					'foo',
					'    bar',
					'',
					'foo'
				),
				array(
					'<t><p>foo',
					'    bar</p>',
					'',
					'<p>foo</p></t>'
				)
			),
			array(
				array(
					'>     code',
					'>     more code',
					'> ',
					'> foo',
					'',
					'bar'
				),
				array(
					'<r><QUOTE><i>&gt;     </i><CODE>code',
					'<i>&gt;     </i>more code</CODE>',
					'<i>&gt; </i>',
					'<i>&gt; </i><p>foo</p></QUOTE>',
					'',
					'<p>bar</p></r>'
				)
			),
			array(
				array(
					'>     code',
					'>     more code',
					'> ',
					'> >     another block',
					'',
					'bar'
				),
				array(
					'<r><QUOTE><i>&gt;     </i><CODE>code',
					'<i>&gt;     </i>more code</CODE>',
					'<i>&gt; </i>',
					'<QUOTE><i>&gt; &gt;     </i><CODE>another block</CODE></QUOTE></QUOTE>',
					'',
					'<p>bar</p></r>'
				)
			),
			array(
				array(
					'> >     code',
					'> >     more code',
					'> ',
					'>       another block',
					'',
					'bar'
				),
				array(
					'<r><QUOTE><QUOTE><i>&gt; &gt;     </i><CODE>code',
					'<i>&gt; &gt;     </i>more code</CODE></QUOTE>',
					'<i>&gt; </i>',
					'<i>&gt;     </i><CODE>  another block</CODE></QUOTE>',
					'',
					'<p>bar</p></r>'
				)
			),
			array(
				array(
					'    ## foo ##',
					'',
					'bar'
				),
				array(
					'<r><i>    </i><CODE>## foo ##</CODE>',
					'',
					'<p>bar</p></r>'
				)
			),
			array(
				array(
					'*foo',
					'',
					'    *bar',
					'baz*'
				),
				array(
					'<r><p>*foo</p>',
					'',
					'<i>    </i><CODE>*bar</CODE>',
					'<p>baz*</p></r>'
				)
			),
			array(
				array(
					'    foo',
					'*bar*'
				),
				array(
					'<r><i>    </i><CODE>foo</CODE>',
					'<p><EM><s>*</s>bar<e>*</e></EM></p></r>'
				)
			),
			// Lists
			array(
				array(
					'* 0',
					' * 1',
					'  * 2',
					'   * 3',
					'    * 4',
					'     * 5',
					'      * 6',
					'       * 7',
					'        * 8',
					'         * 9'
				),
				array(
					'<r><LIST><LI><s>* </s>0',
					' <LIST><LI><s>* </s>1</LI>',
					'  <LI><s>* </s>2</LI>',
					'   <LI><s>* </s>3</LI>',
					'    <LI><s>* </s>4',
					'     <LIST><LI><s>* </s>5</LI>',
					'      <LI><s>* </s>6</LI>',
					'       <LI><s>* </s>7</LI>',
					'        <LI><s>* </s>8',
					'         <LIST><LI><s>* </s>9</LI></LIST></LI></LIST></LI></LIST></LI></LIST></r>'
				)
			),
			array(
				array(
					'+ one',
					'+ two'
				),
				array(
					'<r><LIST><LI><s>+ </s>one</LI>',
					'<LI><s>+ </s>two</LI></LIST></r>'
				)
			),
			array(
				array(
					'- one',
					'',
					'- two'
				),
				array(
					'<r><LIST><LI><s>- </s><p>one</p></LI>',
					'',
					'<LI><s>- </s><p>two</p></LI></LIST></r>'
				)
			),
			array(
				array(
					'- one',
					'  - foo',
					'  - bar',
					'',
					'- two',
					'  - bar',
					'  - baz',
					'',
					'- three'
				),
				array(
					'<r><LIST><LI><s>- </s><p>one</p>',
					'  <LIST><LI><s>- </s>foo</LI>',
					'  <LI><s>- </s>bar</LI></LIST></LI>',
					'',
					'<LI><s>- </s><p>two</p>',
					'  <LIST><LI><s>- </s>bar</LI>',
					'  <LI><s>- </s>baz</LI></LIST></LI>',
					'',
					'<LI><s>- </s><p>three</p></LI></LIST></r>',
				)
			),
			array(
				array(
					'- one',
					'',
					'  - foo',
					'  - bar',
					'',
					'- two',
					'',
					'- three'
				),
				array(
					'<r><LIST><LI><s>- </s><p>one</p>',
					'',
					'  <LIST><LI><s>- </s>foo</LI>',
					'  <LI><s>- </s>bar</LI></LIST></LI>',
					'',
					'<LI><s>- </s><p>two</p></LI>',
					'',
					'<LI><s>- </s><p>three</p></LI></LIST></r>',
				)
			),
			array(
				array(
					' * **foo**',
					' * *bar*'
				),
				array(
					'<r> <LIST><LI><s>* </s><STRONG><s>**</s>foo<e>**</e></STRONG></LI>',
					' <LI><s>* </s><EM><s>*</s>bar<e>*</e></EM></LI></LIST></r>'
				)
			),
			array(
				array(
					' - *foo',
					'   bar*'
				),
				array(
					'<r> <LIST><LI><s>- </s><EM><s>*</s>foo',
					'   bar<e>*</e></EM></LI></LIST></r>'
				)
			),
			array(
				array(
					' - *foo',
					' - bar*'
				),
				array(
					'<r> <LIST><LI><s>- </s>*foo</LI>',
					' <LI><s>- </s>bar*</LI></LIST></r>'
				)
			),
			array(
				array(
					' * foo',
					'',
					'',
					'   bar',
					'',
					' * baz'
				),
				array(
					'<r> <LIST><LI><s>* </s><p>foo</p>',
					'',
					'',
					'   <p>bar</p></LI>',
					'',
					' <LI><s>* </s><p>baz</p></LI></LIST></r>'
				)
			),
			array(
				array(
					'1. one',
					'2. two'
				),
				array(
					'<r><LIST type="decimal"><LI><s>1. </s>one</LI>',
					'<LI><s>2. </s>two</LI></LIST></r>'
				)
			),
			array(
				array(
					'* foo',
					'',
					'> bar'
				),
				array(
					'<r><LIST><LI><s>* </s>foo</LI></LIST>',
					'',
					'<QUOTE><i>&gt; </i><p>bar</p></QUOTE></r>'
				)
			),
			// atx-style headers
			array(
				'# H1',
				'<r><H1><s># </s>H1</H1></r>'
			),
			array(
				'###### H6',
				'<r><H6><s>###### </s>H6</H6></r>'
			),
			array(
				'####### H7',
				'<r><H6><s>####### </s>H7</H6></r>'
			),
			array(
				'# H1 #',
				'<r><H1><s># </s>H1<e> #</e></H1></r>'
			),
			array(
				'### H3 # H3 ####',
				'<r><H3><s>### </s>H3 # H3<e> ####</e></H3></r>'
			),
			array(
				'### foo *bar*',
				'<r><H3><s>### </s>foo <EM><s>*</s>bar<e>*</e></EM></H3></r>'
			),
			array(
				"*foo\n### bar*",
				"<r><p>*foo</p>\n<H3><s>### </s>bar*</H3></r>"
			),
			array(
				"*foo\n### bar*\nbaz*",
				"<r><p>*foo</p>\n<H3><s>### </s>bar*</H3>\n<p>baz*</p></r>"
			),
			array(
				"foo\n\n### bar\n\nbaz",
				"<r><p>foo</p>\n\n<H3><s>### </s>bar</H3>\n\n<p>baz</p></r>"
			),
			array(
				"foo\n\n### bar\n\nbaz",
				"<r><p>foo</p>\n\n<H3><s>### </s>bar</H3>\n\n<p>baz</p></r>"
			),
			array(
				array(
					'> > foo',
					'> ',
					'> # BAR',
					'> ',
					'> baz',
					'',
					'text'
				),
				array(
					'<r><QUOTE><QUOTE><i>&gt; &gt; </i><p>foo</p></QUOTE>',
					'<i>&gt; </i>',
					'<i>&gt; </i><H1><s># </s>BAR</H1>',
					'<i>&gt; </i>',
					'<i>&gt; </i><p>baz</p></QUOTE>',
					'',
					'<p>text</p></r>'
				)
			),
			// Setext-style headers
			array(
				array(
					'foo',
					'===',
					'bar'
				),
				array(
					'<r><H1>foo<e>',
					'===</e></H1>',
					'<p>bar</p></r>'
				)
			),
			array(
				array(
					'foo',
					'---',
					'bar'
				),
				array(
					'<r><H2>foo<e>',
					'---</e></H2>',
					'<p>bar</p></r>'
				)
			),
			array(
				array(
					'foo ',
					'--- ',
					'bar '
				),
				array(
					'<r><H2>foo<e> ',
					'--- </e></H2>',
					'<p>bar</p> </r>'
				)
			),
			array(
				array(
					'foo',
					'=-=',
					'bar'
				),
				array(
					'<t><p>foo',
					'=-=',
					'bar</p></t>'
				)
			),
			array(
				array(
					'foo',
					'= = =',
					'bar'
				),
				array(
					'<t><p>foo',
					'= = =',
					'bar</p></t>'
				)
			),
			array(
				array(
					'> foo',
					'> -'
				),
				array(
					'<r><QUOTE><i>&gt; </i><H2>foo<e>',
					'&gt; -</e></H2></QUOTE></r>'
				)
			),
			array(
				array(
					'> foo',
					'> > -'
				),
				array(
					'<r><QUOTE><i>&gt; </i><p>foo</p>',
					'<QUOTE><i>&gt; &gt; </i><p>-</p></QUOTE></QUOTE></r>'
				)
			),
			array(
				// NOTE: implementations vary wildly on that one. The old Markdown and PHP Markdown
				//       both interpret it as an header whose text content is "> foo" but most other
				//       implementations interpret it as an header inside of a blockquote. Here we
				//       choose a different path: ignore the header altogether to prevent an
				//       accidental dash to turn the last line of a blockquote into a header
				array(
					'> foo',
					'-'
				),
				array(
					'<r><QUOTE><i>&gt; </i><p>foo',
					'-</p></QUOTE></r>'
				)
			),
			array(
				// NOTE: implementations vary wildly. Same as for blockquotes, a loose dash should
				//       not create headers
				array(
					'- foo',
					'-'
				),
				array(
					'<r><LIST><LI><s>- </s>foo',
					'-</LI></LIST></r>'
				)
			),
			array(
				array(
					'    code',
					'-'
				),
				array(
					'<r><i>    </i><CODE>code</CODE>',
					'<p>-</p></r>'
				)
			),
			array(
				'-',
				'<t><p>-</p></t>'
			),
			array(
				" \n-",
				"<t> \n<p>-</p></t>"
			),
			array(
				array(
					'## foo',
					'======'
				),
				array(
					'<r><H2><s>## </s>foo</H2>',
					'<p>======</p></r>'
				)
			),
			array(
				array(
					'foo ',
					'===='
				),
				array(
					'<r><H1>foo<e> ',
					'====</e></H1></r>'
				)
			),
			array(
				array(
					'foo',
					'===',
					'==='
				),
				array(
					'<r><H1>foo<e>',
					'===</e></H1>',
					'<p>===</p></r>'
				)
			),
			// Horizontal rules
			array(
				array(
					'foo',
					'',
					'---',
					'',
					'bar'
				),
				array(
					'<r><p>foo</p>',
					'',
					'<HR>---</HR>',
					'',
					'<p>bar</p></r>'
				)
			),
			array(
				array(
					'foo',
					' _ _ _ ',
					'bar'
				),
				array(
					'<r><p>foo</p>',
					'<HR> _ _ _ </HR>',
					'<p>bar</p></r>'
				)
			),
			array(
				array(
					'foo',
					'___',
					'bar'
				),
				array(
					'<r><p>foo</p>',
					'<HR>___</HR>',
					'<p>bar</p></r>'
				)
			),
			array(
				array(
					'foo',
					'***',
					'bar'
				),
				array(
					'<r><p>foo</p>',
					'<HR>***</HR>',
					'<p>bar</p></r>'
				)
			),
			array(
				array(
					'foo',
					'* * *',
					'bar'
				),
				array(
					'<r><p>foo</p>',
					'<HR>* * *</HR>',
					'<p>bar</p></r>'
				)
			),
			array(
				array(
					'foo',
					'   * * * * *   ',
					'bar'
				),
				array(
					'<r><p>foo</p>',
					'<HR>   * * * * *   </HR>',
					'<p>bar</p></r>'
				)
			),
			array(
				array(
					' - foo',
					'   ***',
					'   bar'
				),
				array(
					'<r> <LIST><LI><s>- </s>foo',
					'   ***',
					'   bar</LI></LIST></r>'
				)
			),
			array(
				'>  *** ',
				'<r><QUOTE><i>&gt; </i><HR> *** </HR></QUOTE></r>'
			),
			// Links
			array(
				'Go to [that site](http://example.org) now!',
				'<r><p>Go to <URL url="http://example.org"><s>[</s>that site<e>](http://example.org)</e></URL> now!</p></r>'
			),
			array(
				'Go to [that site] (http://example.org) now!',
				'<r><p>Go to <URL url="http://example.org"><s>[</s>that site<e>] (http://example.org)</e></URL> now!</p></r>'
			),
			array(
				'En route to [Mars](http://en.wikipedia.org/wiki/Mars_(disambiguation\))!',
				'<r><p>En route to <URL url="http://en.wikipedia.org/wiki/Mars_%28disambiguation%29"><s>[</s>Mars<e>](http://en.wikipedia.org/wiki/Mars_(disambiguation\))</e></URL>!</p></r>'
			),
			array(
				'Go to [\\[x\\[x\\]x\\]](http://example.org/?foo[]=1&bar\\[\\]=1) now!',
				'<r><p>Go to <URL url="http://example.org/?foo%5B%5D=1&amp;bar%5B%5D=1"><s>[</s>\\[x\\[x\\]x\\]<e>](http://example.org/?foo[]=1&amp;bar\\[\\]=1)</e></URL> now!</p></r>'
			),
			array(
				'Check out my [~~lame~~ cool site](http://example.org) now!',
				'<r><p>Check out my <URL url="http://example.org"><s>[</s><DEL><s>~~</s>lame<e>~~</e></DEL> cool site<e>](http://example.org)</e></URL> now!</p></r>'
			),
			array(
				'This is [an example](http://example.com/ "Link title") inline link.',
				'<r><p>This is <URL title="Link title" url="http://example.com/"><s>[</s>an example<e>](http://example.com/ "Link title")</e></URL> inline link.</p></r>'
			),
			array(
				'This is [an example](http://example.com/ ""Link title"") inline link.',
				'<r><p>This is <URL title="&quot;Link title&quot;" url="http://example.com/"><s>[</s>an example<e>](http://example.com/ ""Link title"")</e></URL> inline link.</p></r>'
			),
			array(
				'[not a link]',
				'<t><p>[not a link]</p></t>'
			),
			// Images
			array(
				'.. ![Alt text](http://example.org/img.png) ..',
				'<r><p>.. <IMG alt="Alt text" src="http://example.org/img.png"><s>![</s>Alt text<e>](http://example.org/img.png)</e></IMG> ..</p></r>'
			),
			array(
				'.. ![Alt text](http://example.org/img.png "Image title") ..',
				'<r><p>.. <IMG alt="Alt text" src="http://example.org/img.png" title="Image title"><s>![</s>Alt text<e>](http://example.org/img.png "Image title")</e></IMG> ..</p></r>'
			),
			array(
				'.. ![Alt \\[text\\]](http://example.org/img.png "\\"Image title\\"") ..',
				'<r><p>.. <IMG alt="Alt [text]" src="http://example.org/img.png" title="&quot;Image title&quot;"><s>![</s>Alt \\[text\\]<e>](http://example.org/img.png "\\"Image title\\"")</e></IMG> ..</p></r>'
			),
			array(
				'.. ![Alt text](http://example.org/img.png "Image (title)") ..',
				'<r><p>.. <IMG alt="Alt text" src="http://example.org/img.png" title="Image (title)"><s>![</s>Alt text<e>](http://example.org/img.png "Image (title)")</e></IMG> ..</p></r>'
			),
			// Images in links
			array(
				'.. [![Alt text](http://example.org/img.png)](http://example.org/) ..',
				'<r><p>.. <URL url="http://example.org/"><s>[</s><IMG alt="Alt text" src="http://example.org/img.png"><s>![</s>Alt text<e>](http://example.org/img.png)</e></IMG><e>](http://example.org/)</e></URL> ..</p></r>'
			),
			// Inline code
			array(
				'.. `foo` `bar` ..',
				'<r><p>.. <C><s>`</s>foo<e>`</e></C> <C><s>`</s>bar<e>`</e></C> ..</p></r>'
			),
			array(
				'.. `foo `` bar` ..',
				'<r><p>.. <C><s>`</s>foo `` bar<e>`</e></C> ..</p></r>'
			),
			array(
				'.. `foo ``` bar` ..',
				'<r><p>.. <C><s>`</s>foo ``` bar<e>`</e></C> ..</p></r>'
			),
			array(
				'.. ``foo`` ``bar`` ..',
				'<r><p>.. <C><s>``</s>foo<e>``</e></C> <C><s>``</s>bar<e>``</e></C> ..</p></r>'
			),
			array(
				'.. ``foo `bar` baz`` ..',
				'<r><p>.. <C><s>``</s>foo `bar` baz<e>``</e></C> ..</p></r>'
			),
			array(
				'.. `foo\\` \\`b\\\\ar` ..',
				'<r><p>.. <C><s>`</s>foo\\` \\`b\\\\ar<e>`</e></C> ..</p></r>'
			),
			array(
				'.. `[foo](http://example.org)` ..',
				'<r><p>.. <C><s>`</s>[foo](http://example.org)<e>`</e></C> ..</p></r>'
			),
			array(
				'.. `![foo](http://example.org)` ..',
				'<r><p>.. <C><s>`</s>![foo](http://example.org)<e>`</e></C> ..</p></r>'
			),
			array(
				'.. `x` ..',
				'<r><p>.. <C><s>`</s>x<e>`</e></C> ..</p></r>'
			),
			array(
				'.. ``x`` ..',
				'<r><p>.. <C><s>``</s>x<e>``</e></C> ..</p></r>'
			),
			array(
				"`foo\nbar`",
				"<r><p><C><s>`</s>foo\nbar<e>`</e></C></p></r>"
			),
			array(
				"`foo\n\nbar`",
				"<t><p>`foo</p>\n\n<p>bar`</p></t>"
			),
			// Strikethrough
			array(
				'.. ~~foo~~ ~~bar~~ ..',
				'<r><p>.. <DEL><s>~~</s>foo<e>~~</e></DEL> <DEL><s>~~</s>bar<e>~~</e></DEL> ..</p></r>'
			),
			array(
				'.. ~~foo~bar~~ ..',
				'<r><p>.. <DEL><s>~~</s>foo~bar<e>~~</e></DEL> ..</p></r>'
			),
			array(
				'.. ~~foo\\~~ ~~bar~~ ..',
				'<r><p>.. <DEL><s>~~</s>foo\\~~ <e>~~</e></DEL>bar~~ ..</p></r>'
			),
			array(
				'.. ~~~~ ..',
				'<t><p>.. ~~~~ ..</p></t>'
			),
			array(
				"~~foo\nbar~~",
				"<r><p><DEL><s>~~</s>foo\nbar<e>~~</e></DEL></p></r>"
			),
			array(
				"~~foo\n\nbar~~",
				"<t><p>~~foo</p>\n\n<p>bar~~</p></t>"
			),
			// Superscript
			array(
				'.. foo^baar^baz 1^2 ..',
				'<r><p>.. foo<SUP><s>^</s>baar<SUP><s>^</s>baz</SUP></SUP> 1<SUP><s>^</s>2</SUP> ..</p></r>'
			),
			array(
				'.. \\^_^ ..',
				'<t><p>.. \^_^ ..</p></t>'
			),
			// Emphasis
			array(
				'xx ***x*****x** xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG><STRONG><s>**</s>x<e>**</e></STRONG> xx</p></r>'
			),
			array(
				'xx ***x****x* xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG><EM><s>*</s>x<e>*</e></EM> xx</p></r>'
			),
			array(
				'xx ***x*** xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			),
			array(
				'xx ***x**x* xx',
				'<r><p>xx <EM><s>*</s><STRONG><s>**</s>x<e>**</e></STRONG>x<e>*</e></EM> xx</p></r>'
			),
			array(
				'xx ***x*x** xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM>x<e>**</e></STRONG> xx</p></r>'
			),
			array(
				'xx **x*****x*** xx',
				'<r><p>xx <STRONG><s>**</s>x<e>**</e></STRONG><STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			),
			array(
				'xx **x****x** xx',
				'<r><p>xx <STRONG><s>**</s>x<e>**</e></STRONG><STRONG><s>**</s>x<e>**</e></STRONG> xx</p></r>'
			),
			array(
				'xx **x***x* xx',
				'<r><p>xx <STRONG><s>**</s>x<e>**</e></STRONG><EM><s>*</s>x<e>*</e></EM> xx</p></r>'
			),
			array(
				'xx **x** xx',
				'<r><p>xx <STRONG><s>**</s>x<e>**</e></STRONG> xx</p></r>'
			),
			array(
				'xx **x*x** xx',
				'<r><p>xx <STRONG><s>**</s>x*x<e>**</e></STRONG> xx</p></r>'
			),
			array(
				'xx *x*****x*** xx',
				'<r><p>xx <EM><s>*</s>x<e>*</e></EM>*<STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			),
			array(
				'xx *x****x*** xx',
				'<r><p>xx <EM><s>*</s>x<e>*</e></EM><STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			),
			array(
				'xx *x**x* xx',
				'<r><p>xx <EM><s>*</s>x**x<e>*</e></EM> xx</p></r>'
			),
			array(
				'xx *x* xx',
				'<r><p>xx <EM><s>*</s>x<e>*</e></EM> xx</p></r>'
			),
			array(
				'xx *x**x*x** xx',
				'<r><p>xx <EM><s>*</s>x<STRONG><s>**</s>x</STRONG><e>*</e></EM><STRONG>x<e>**</e></STRONG> xx</p></r>'
			),
			array(
				"*foo\nbar*",
				"<r><p><EM><s>*</s>foo\nbar<e>*</e></EM></p></r>"
			),
			array(
				"*foo\n\nbar*",
				"<t><p>*foo</p>\n\n<p>bar*</p></t>"
			),
			array(
				"***foo*\n\nbar**",
				"<r><p>**<EM><s>*</s>foo<e>*</e></EM></p>\n\n<p>bar**</p></r>"
			),
			array(
				"***foo**\n\nbar*",
				"<r><p>*<STRONG><s>**</s>foo<e>**</e></STRONG></p>\n\n<p>bar*</p></r>"
			),
			array(
				'xx _x_ xx',
				'<r><p>xx <EM><s>_</s>x<e>_</e></EM> xx</p></r>'
			),
			array(
				'xx __x__ xx',
				'<r><p>xx <STRONG><s>__</s>x<e>__</e></STRONG> xx</p></r>'
			),
			array(
				'xx foo_bar_baz xx',
				'<t><p>xx foo_bar_baz xx</p></t>'
			),
			array(
				'xx foo__bar__baz xx',
				'<r><p>xx foo<STRONG><s>__</s>bar<e>__</e></STRONG>baz xx</p></r>'
			),
			array(
				'x _foo_',
				'<r><p>x <EM><s>_</s>foo<e>_</e></EM></p></r>'
			),
			array(
				'_foo_ x',
				'<r><p><EM><s>_</s>foo<e>_</e></EM> x</p></r>'
			),
			array(
				'_foo_',
				'<r><p><EM><s>_</s>foo<e>_</e></EM></p></r>'
			),
			array(
				'xx ***x******x*** xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG><STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			),
			array(
				'xx ***x*******x*** xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG>*<STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			),
			array(
				'xx *****x***** xx',
				'<r><p>xx **<STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG>** xx</p></r>'
			),
			array(
				'xx **x*x*** xx',
				'<r><p>xx <STRONG><s>**</s>x<EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			),
			array(
				'xx *x**x*** xx',
				'<r><p>xx <EM><s>*</s>x<STRONG><s>**</s>x<e>**</e></STRONG><e>*</e></EM> xx</p></r>'
			),
			array(
				'\\\\*foo*',
				'<r><p>\\\\<EM><s>*</s>foo<e>*</e></EM></p></r>'
			),
			array(
				'*\\\\*foo*',
				'<r><p><EM><s>*</s>\\\\<e>*</e></EM>foo*</p></r>'
			),
			array(
				'*\\\\*foo*',
				'<r><p><EM><s>*</s>\\\\<e>*</e></EM>foo*</p></r>'
			),
			// Forced line breaks
			array(
				array(
					'first line  ',
					'second line  ',
					'third line'
				),
				array(
					'<t><p>first line  <br/>',
					'second line  <br/>',
					'third line</p></t>'
				),
			),
			array(
				array(
					'first line  ',
					'second line  '
				),
				array(
					'<t><p>first line  <br/>',
					'second line</p>  </t>'
				),
			),
			array(
				array(
					'> first line  ',
					'> second line  ',
					'',
					'outside quote'
				),
				array(
					'<r><QUOTE><i>&gt; </i><p>first line  <br/>',
					'<i>&gt; </i>second line</p>  </QUOTE>',
					'',
					'<p>outside quote</p></r>'
				),
			),
			array(
				array(
					'    first line  ',
					'    second line  ',
					'',
					'outside code'
				),
				array(
					'<r><i>    </i><CODE>first line  ',
					'<i>    </i>second line  </CODE>',
					'',
					'<p>outside code</p></r>'
				),
			),
			array(
				array(
					' * first item  ',
					'   still the first item  ',
					' * second item',
					'',
					'outside list'
				),
				array(
					'<r> <LIST><LI><s>* </s>first item  <br/>',
					'   still the first item  </LI>',
					' <LI><s>* </s>second item</LI></LIST>',
					'',
					'<p>outside list</p></r>'
				),
			),
			array(
				array(
					'foo  ',
					'---  ',
					'bar  '
				),
				array(
					'<r><H2>foo<e>  ',
					'---  </e></H2>',
					'<p>bar</p>  </r>'
				)
			),
		));
	}

	public function getRenderingTests()
	{
		return self::fixTests(array(
			array(
				'> foo',
				'<blockquote><p>foo</p></blockquote>'
			),
			array(
				array(
					'> > foo',
					'> ',
					'> bar',
					'',
					'baz'
				),
				array(
					'<blockquote><blockquote><p>foo</p></blockquote>',
					'',
					'<p>bar</p></blockquote>',
					'',
					'<p>baz</p>'
				)
			),
			array(
				array(
					'foo',
					'',
					'## bar',
					'',
					'baz'
				),
				array(
					'<p>foo</p>',
					'',
					'<h2>bar</h2>',
					'',
					'<p>baz</p>'
				)
			),
			array(
				array(
					'* 0',
					' * 1',
					'  * 2',
					'   * 3',
					'    * 4',
					'     * 5',
					'      * 6',
					'       * 7',
					'        * 8',
					'         * 9'
				),
				array(
					'<ul><li>0',
					' <ul><li>1</li>',
					'  <li>2</li>',
					'   <li>3</li>',
					'    <li>4',
					'     <ul><li>5</li>',
					'      <li>6</li>',
					'       <li>7</li>',
					'        <li>8',
					'         <ul><li>9</li></ul></li></ul></li></ul></li></ul>'
				)
			),
			array(
				array(
					'1. one',
					'2. two'
				),
				array(
					'<ol><li>one</li>',
					'<li>two</li></ol>'
				)
			),
			array(
				array(
					'- one',
					'  - foo',
					'  - bar',
					'',
					'- two',
					'  - bar',
					'  - baz',
					'',
					'- three'
				),
				array(
					'<ul><li><p>one</p>',
					'  <ul><li>foo</li>',
					'  <li>bar</li></ul></li>',
					'',
					'<li><p>two</p>',
					'  <ul><li>bar</li>',
					'  <li>baz</li></ul></li>',
					'',
					'<li><p>three</p></li></ul>'
				),
			),
			array(
				'[Link text](http://example.org)',
				'<p><a href="http://example.org">Link text</a></p>'
			),
			array(
				'[Link text](http://example.org "Link title")',
				'<p><a href="http://example.org" title="Link title">Link text</a></p>'
			),
		));
	}

	protected static function fixTests($tests)
	{
		foreach ($tests as &$test)
		{
			if (is_array($test[0]))
			{
				$test[0] = implode("\n", $test[0]);
			}

			if (is_array($test[1]))
			{
				$test[1] = implode("\n", $test[1]);
			}

			if (!isset($test[2]))
			{
				$test[2] = array();
			}

			$callback = (isset($test[3])) ? $test[3] : null;
			$test[3] = function ($configurator) use ($callback)
			{
				if (isset($callback))
				{
					$callback($configurator);
				}
				$configurator->addHTML5Rules();
			};
		}

		return $tests;
	}
}