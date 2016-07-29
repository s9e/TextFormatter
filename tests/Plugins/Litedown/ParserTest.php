<?php

namespace s9e\TextFormatter\Tests\Plugins\Litedown;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Litedown\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Litedown\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return self::fixTests([
			[
				// Ensure that automatic line breaks can be enabled
				"First\nSecond",
				"<t><p>First<br/>\nSecond</p></t>",
				[],
				function ($configurator)
				{
					$configurator->rootRules->enableAutoLineBreaks();
				}
			],
			// Paragraphs and quotes
			[
				'foo',
				'<t><p>foo</p></t>'
			],
			[
				"foo\n\nbar",
				"<t><p>foo</p>\n\n<p>bar</p></t>"
			],
			[
				'> foo',
				'<r><QUOTE><i>&gt; </i><p>foo</p></QUOTE></r>'
			],
			[
				[
					'> > foo',
					'> ',
					'> bar',
					'',
					'baz'
				],
				[
					'<r><QUOTE><QUOTE><i>&gt; &gt; </i><p>foo</p></QUOTE>',
					'<i>&gt; </i>',
					'<i>&gt; </i><p>bar</p></QUOTE>',
					'',
					'<p>baz</p></r>'
				]
			],
			[
				[
					'> > foo',
					'> ',
					'> > bar',
					'',
					'baz'
				],
				[
					'<r><QUOTE><QUOTE><i>&gt; &gt; </i><p>foo</p>',
					'<i>&gt; </i>',
					'<i>&gt; &gt; </i><p>bar</p></QUOTE></QUOTE>',
					'',
					'<p>baz</p></r>'
				]
			],
			[
				[
					'> > foo',
					'',
					'> > bar',
					'',
					'baz'
				],
				[
					'<r><QUOTE><QUOTE><i>&gt; &gt; </i><p>foo</p>',
					'',
					'<i>&gt; &gt; </i><p>bar</p></QUOTE></QUOTE>',
					'',
					'<p>baz</p></r>'
				]
			],
			[
				[
					'> foo',
					'bar',
					'baz',
					'',
					'quux'
				],
				[
					'<r><QUOTE><i>&gt; </i><p>foo',
					'bar',
					'baz</p></QUOTE>',
					'',
					'<p>quux</p></r>'
				]
			],
			[
				[
					'foo',
					'> bar',
					'baz',
					'',
					'quux'
				],
				[
					'<r><p>foo</p>',
					'<QUOTE><i>&gt; </i><p>bar',
					'baz</p></QUOTE>',
					'',
					'<p>quux</p></r>'
				]
			],
			[
				[
					'> foo',
					'',
					'',
					'> bar',
				],
				[
					'<r><QUOTE><i>&gt; </i><p>foo</p></QUOTE>',
					'',
					'',
					'<QUOTE><i>&gt; </i><p>bar</p></QUOTE></r>'
				]
			],
			[
				[
					'> > foo',
					'>',
					'>',
					'> > bar',
				],
				[
					'<r><QUOTE><QUOTE><i>&gt; &gt; </i><p>foo</p></QUOTE>',
					'<i>&gt;</i>',
					'<i>&gt;</i>',
					'<QUOTE><i>&gt; &gt; </i><p>bar</p></QUOTE></QUOTE></r>'
				]
			],
			[
				[
					'> > foo',
					'',
					'',
					'> > bar',
				],
				[
					'<r><QUOTE><QUOTE><i>&gt; &gt; </i><p>foo</p></QUOTE></QUOTE>',
					'',
					'',
					'<QUOTE><QUOTE><i>&gt; &gt; </i><p>bar</p></QUOTE></QUOTE></r>'
				]
			],
			// Indented code blocks
			[
				[
					'    code',
					'    more code',
					'',
					'foo'
				],
				[
					'<r><i>    </i><CODE>code',
					'<i>    </i>more code</CODE>',
					'',
					'<p>foo</p></r>'
				]
			],
			[
				[
					'    code',
					"\tmore code",
					'',
					'foo'
				],
				[
					'<r><i>    </i><CODE>code',
					"<i>\t</i>more code</CODE>",
					'',
					'<p>foo</p></r>'
				]
			],
			[
				[
					'    code',
					"   \tmore code",
					'',
					'foo'
				],
				[
					'<r><i>    </i><CODE>code',
					"<i>   \t</i>more code</CODE>",
					'',
					'<p>foo</p></r>'
				]
			],
			[
				[
					'    code',
					"    \tmore code",
					'',
					'foo'
				],
				[
					'<r><i>    </i><CODE>code',
					"<i>    </i>\tmore code</CODE>",
					'',
					'<p>foo</p></r>'
				]
			],
			[
				[
					'bar',
					'',
					'    code',
					'    more code',
					'',
					'foo'
				],
				[
					'<r><p>bar</p>',
					'',
					'<i>    </i><CODE>code',
					'<i>    </i>more code</CODE>',
					'',
					'<p>foo</p></r>'
				]
			],
			[
				[
					'bar',
					'',
					'    code',
					'',
					'    more code',
					'',
					'foo'
				],
				[
					'<r><p>bar</p>',
					'',
					'<i>    </i><CODE>code',
					'',
					'<i>    </i>more code</CODE>',
					'',
					'<p>foo</p></r>'
				]
			],
			[
				[
					'foo',
					'    bar',
					'',
					'foo'
				],
				[
					'<t><p>foo',
					'    bar</p>',
					'',
					'<p>foo</p></t>'
				]
			],
			[
				[
					'>     code',
					'>     more code',
					'> ',
					'> foo',
					'',
					'bar'
				],
				[
					'<r><QUOTE><i>&gt;     </i><CODE>code',
					'<i>&gt;     </i>more code</CODE>',
					'<i>&gt; </i>',
					'<i>&gt; </i><p>foo</p></QUOTE>',
					'',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'>     code',
					'>     more code',
					'> ',
					'> >     another block',
					'',
					'bar'
				],
				[
					'<r><QUOTE><i>&gt;     </i><CODE>code',
					'<i>&gt;     </i>more code</CODE>',
					'<i>&gt; </i>',
					'<QUOTE><i>&gt; &gt;     </i><CODE>another block</CODE></QUOTE></QUOTE>',
					'',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'> >     code',
					'> >     more code',
					'> ',
					'>       another block',
					'',
					'bar'
				],
				[
					'<r><QUOTE><QUOTE><i>&gt; &gt;     </i><CODE>code',
					'<i>&gt; &gt;     </i>more code</CODE></QUOTE>',
					'<i>&gt; </i>',
					'<i>&gt;     </i><CODE>  another block</CODE></QUOTE>',
					'',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'    ## foo ##',
					'',
					'bar'
				],
				[
					'<r><i>    </i><CODE>## foo ##</CODE>',
					'',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'*foo',
					'',
					'    *bar',
					'baz*'
				],
				[
					'<r><p>*foo</p>',
					'',
					'<i>    </i><CODE>*bar</CODE>',
					'<p>baz*</p></r>'
				]
			],
			[
				[
					'    foo',
					'*bar*'
				],
				[
					'<r><i>    </i><CODE>foo</CODE>',
					'<p><EM><s>*</s>bar<e>*</e></EM></p></r>'
				]
			],
			// Lists
			[
				[
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
				],
				[
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
				]
			],
			[
				[
					'+ one',
					'+ two'
				],
				[
					'<r><LIST><LI><s>+ </s>one</LI>',
					'<LI><s>+ </s>two</LI></LIST></r>'
				]
			],
			[
				[
					'- one',
					'',
					'- two'
				],
				[
					'<r><LIST><LI><s>- </s><p>one</p></LI>',
					'',
					'<LI><s>- </s><p>two</p></LI></LIST></r>'
				]
			],
			[
				[
					'- one',
					'  - foo',
					'  - bar',
					'',
					'- two',
					'  - bar',
					'  - baz',
					'',
					'- three'
				],
				[
					'<r><LIST><LI><s>- </s><p>one</p>',
					'  <LIST><LI><s>- </s>foo</LI>',
					'  <LI><s>- </s>bar</LI></LIST></LI>',
					'',
					'<LI><s>- </s><p>two</p>',
					'  <LIST><LI><s>- </s>bar</LI>',
					'  <LI><s>- </s>baz</LI></LIST></LI>',
					'',
					'<LI><s>- </s><p>three</p></LI></LIST></r>',
				]
			],
			[
				[
					'- one',
					'',
					'  - foo',
					'  - bar',
					'',
					'- two',
					'',
					'- three'
				],
				[
					'<r><LIST><LI><s>- </s><p>one</p>',
					'',
					'  <LIST><LI><s>- </s>foo</LI>',
					'  <LI><s>- </s>bar</LI></LIST></LI>',
					'',
					'<LI><s>- </s><p>two</p></LI>',
					'',
					'<LI><s>- </s><p>three</p></LI></LIST></r>',
				]
			],
			[
				[
					' * **foo**',
					' * *bar*'
				],
				[
					'<r> <LIST><LI><s>* </s><STRONG><s>**</s>foo<e>**</e></STRONG></LI>',
					' <LI><s>* </s><EM><s>*</s>bar<e>*</e></EM></LI></LIST></r>'
				]
			],
			[
				[
					' - *foo',
					'   bar*'
				],
				[
					'<r> <LIST><LI><s>- </s><EM><s>*</s>foo',
					'   bar<e>*</e></EM></LI></LIST></r>'
				]
			],
			[
				[
					' - *foo',
					' - bar*'
				],
				[
					'<r> <LIST><LI><s>- </s>*foo</LI>',
					' <LI><s>- </s>bar*</LI></LIST></r>'
				]
			],
			[
				[
					' * foo',
					'',
					'   bar',
					'',
					' * baz'
				],
				[
					'<r> <LIST><LI><s>* </s><p>foo</p>',
					'',
					'   <p>bar</p></LI>',
					'',
					' <LI><s>* </s><p>baz</p></LI></LIST></r>'
				]
			],
			[
				[
					' * foo',
					'',
					'',
					'   bar',
					'',
					' * baz'
				],
				[
					'<r> <LIST><LI><s>* </s>foo</LI></LIST>',
					'',
					'',
					'   <p>bar</p>',
					'',
					' <LIST><LI><s>* </s>baz</LI></LIST></r>'
				]
			],
			[
				[
					'1. one',
					'2. two'
				],
				[
					'<r><LIST type="decimal"><LI><s>1. </s>one</LI>',
					'<LI><s>2. </s>two</LI></LIST></r>'
				]
			],
			[
				[
					'2. two',
					'3. three'
				],
				[
					'<r><LIST start="2" type="decimal"><LI><s>2. </s>two</LI>',
					'<LI><s>3. </s>three</LI></LIST></r>'
				]
			],
			[
				[
					' * foo',
					' * bar',
					'',
					'',
					' 1. one',
					' 2. two'
				],
				[
					'<r> <LIST><LI><s>* </s>foo</LI>',
					' <LI><s>* </s>bar</LI></LIST>',
					'',
					'',
					' <LIST type="decimal"><LI><s>1. </s>one</LI>',
					' <LI><s>2. </s>two</LI></LIST></r>'
				]
			],
			[
				[
					' * foo',
					' * bar',
					'',
					'',
					' * one',
					' * two'
				],
				[
					'<r> <LIST><LI><s>* </s>foo</LI>',
					' <LI><s>* </s>bar</LI></LIST>',
					'',
					'',
					' <LIST><LI><s>* </s>one</LI>',
					' <LI><s>* </s>two</LI></LIST></r>'
				]
			],
			[
				[
					'> * foo',
					'> * bar',
					'>',
					'>',
					'> 1. one',
					'> 2. two'
				],
				[
					'<r><QUOTE><i>&gt; </i><LIST><LI><s>* </s>foo</LI>',
					'<i>&gt; </i><LI><s>* </s>bar</LI></LIST>',
					'<i>&gt;</i>',
					'<i>&gt;</i>',
					'<i>&gt; </i><LIST type="decimal"><LI><s>1. </s>one</LI>',
					'<i>&gt; </i><LI><s>2. </s>two</LI></LIST></QUOTE></r>'
				]
			],
			[
				[
					'* foo',
					'',
					'> bar'
				],
				[
					'<r><LIST><LI><s>* </s>foo</LI></LIST>',
					'',
					'<QUOTE><i>&gt; </i><p>bar</p></QUOTE></r>'
				]
			],
			// atx-style headers
			[
				'# H1',
				'<r><H1><s># </s>H1</H1></r>'
			],
			[
				'###### H6',
				'<r><H6><s>###### </s>H6</H6></r>'
			],
			[
				'####### H7',
				'<t><p>####### H7</p></t>'
			],
			[
				'# H1 #',
				'<r><H1><s># </s>H1<e> #</e></H1></r>'
			],
			[
				'### H3 # H3 ####',
				'<r><H3><s>### </s>H3 # H3<e> ####</e></H3></r>'
			],
			[
				'### foo *bar*',
				'<r><H3><s>### </s>foo <EM><s>*</s>bar<e>*</e></EM></H3></r>'
			],
			[
				"*foo\n### bar*",
				"<r><p>*foo</p>\n<H3><s>### </s>bar*</H3></r>"
			],
			[
				"*foo\n### bar*\nbaz*",
				"<r><p>*foo</p>\n<H3><s>### </s>bar*</H3>\n<p>baz*</p></r>"
			],
			[
				"foo\n\n### bar\n\nbaz",
				"<r><p>foo</p>\n\n<H3><s>### </s>bar</H3>\n\n<p>baz</p></r>"
			],
			[
				"foo\n\n### bar\n\nbaz",
				"<r><p>foo</p>\n\n<H3><s>### </s>bar</H3>\n\n<p>baz</p></r>"
			],
			[
				[
					'> > foo',
					'> ',
					'> # BAR',
					'> ',
					'> baz',
					'',
					'text'
				],
				[
					'<r><QUOTE><QUOTE><i>&gt; &gt; </i><p>foo</p></QUOTE>',
					'<i>&gt; </i>',
					'<i>&gt; </i><H1><s># </s>BAR</H1>',
					'<i>&gt; </i>',
					'<i>&gt; </i><p>baz</p></QUOTE>',
					'',
					'<p>text</p></r>'
				]
			],
			[
				// https://github.com/s9e/TextFormatter/issues/14
				'# ',
				'<r><H1><s># </s></H1></r>'
			],
			[
				'# #',
				'<r><H1><s># </s><e>#</e></H1></r>'
			],
			[
				'# foo # #',
				'<r><H1><s># </s>foo #<e> #</e></H1></r>'
			],
			[
				[
					'# H1',
					'1. list'
				],
				[
					'<r><H1><s># </s>H1</H1>',
					'<LIST type="decimal"><LI><s>1. </s>list</LI></LIST></r>'
				]
			],
			[
				[
					'# H1 #',
					'1. list'
				],
				[
					'<r><H1><s># </s>H1<e> #</e></H1>',
					'<LIST type="decimal"><LI><s>1. </s>list</LI></LIST></r>'
				]
			],
			// Setext-style headers
			[
				[
					'foo',
					'===',
					'bar'
				],
				[
					'<r><H1>foo<e>',
					'===</e></H1>',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'foo',
					'---',
					'bar'
				],
				[
					'<r><H2>foo<e>',
					'---</e></H2>',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'foo ',
					'--- ',
					'bar '
				],
				[
					'<r><H2>foo<e> ',
					'--- </e></H2>',
					'<p>bar</p> </r>'
				]
			],
			[
				[
					'foo',
					'=-=',
					'bar'
				],
				[
					'<t><p>foo',
					'=-=',
					'bar</p></t>'
				]
			],
			[
				[
					'foo',
					'= = =',
					'bar'
				],
				[
					'<t><p>foo',
					'= = =',
					'bar</p></t>'
				]
			],
			[
				[
					'> foo',
					'> -'
				],
				[
					'<r><QUOTE><i>&gt; </i><H2>foo<e>',
					'&gt; -</e></H2></QUOTE></r>'
				]
			],
			[
				[
					'> foo',
					'> > -'
				],
				[
					'<r><QUOTE><i>&gt; </i><p>foo</p>',
					'<QUOTE><i>&gt; &gt; </i><p>-</p></QUOTE></QUOTE></r>'
				]
			],
			[
				// NOTE: implementations vary wildly on that one. The old Markdown and PHP Markdown
				//       both interpret it as an header whose text content is "> foo" but most other
				//       implementations interpret it as an header inside of a blockquote. Here we
				//       choose a different path: ignore the header altogether to prevent an
				//       accidental dash to turn the last line of a blockquote into a header
				[
					'> foo',
					'-'
				],
				[
					'<r><QUOTE><i>&gt; </i><p>foo',
					'-</p></QUOTE></r>'
				]
			],
			[
				// NOTE: implementations vary wildly. Same as for blockquotes, a loose dash should
				//       not create headers
				[
					'- foo',
					'-'
				],
				[
					'<r><LIST><LI><s>- </s>foo',
					'-</LI></LIST></r>'
				]
			],
			[
				[
					'    code',
					'-'
				],
				[
					'<r><i>    </i><CODE>code</CODE>',
					'<p>-</p></r>'
				]
			],
			[
				[
					'foo',
					'---',
					'```',
					'bar',
					'```'
				],
				[
					'<r><H2>foo<e>',
					'---</e></H2>',
					'<CODE><s>```</s><i>',
					'</i>bar<i>',
					'</i><e>```</e></CODE></r>'
				]
			],
			[
				[
					'foo  ',
					'---  ',
					'```',
					'bar',
					'```'
				],
				[
					'<r><H2>foo<e>  ',
					'---  </e></H2>',
					'<CODE><s>```</s><i>',
					'</i>bar<i>',
					'</i><e>```</e></CODE></r>'
				]
			],
			[
				'-',
				'<t><p>-</p></t>'
			],
			[
				" \n-",
				"<t> \n<p>-</p></t>"
			],
			[
				[
					'## foo',
					'======'
				],
				[
					'<r><H2><s>## </s>foo</H2>',
					'<p>======</p></r>'
				]
			],
			[
				[
					'foo ',
					'===='
				],
				[
					'<r><H1>foo<e> ',
					'====</e></H1></r>'
				]
			],
			[
				[
					'foo',
					'===',
					'==='
				],
				[
					'<r><H1>foo<e>',
					'===</e></H1>',
					'<p>===</p></r>'
				]
			],
			[
				[
					'foo',
					'===',
					'> xxx'
				],
				[
					'<r><H1>foo<e>',
					'===</e></H1>',
					'<QUOTE><i>&gt; </i><p>xxx</p></QUOTE></r>'
				]
			],
			[
				[
					'foo',
					'===',
					'1. first',
					'2. second'
				],
				[
					'<r><H1>foo<e>',
					'===</e></H1>',
					'<LIST type="decimal"><LI><s>1. </s>first</LI>',
					'<LI><s>2. </s>second</LI></LIST></r>'
				]
			],
			// Horizontal rules
			[
				[
					'foo',
					'',
					'---',
					'',
					'bar'
				],
				[
					'<r><p>foo</p>',
					'',
					'<HR>---</HR>',
					'',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'foo',
					' _ _ _ ',
					'bar'
				],
				[
					'<r><p>foo</p>',
					'<HR> _ _ _ </HR>',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'foo',
					'___',
					'bar'
				],
				[
					'<r><p>foo</p>',
					'<HR>___</HR>',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'foo',
					'***',
					'bar'
				],
				[
					'<r><p>foo</p>',
					'<HR>***</HR>',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'foo',
					'* * *',
					'bar'
				],
				[
					'<r><p>foo</p>',
					'<HR>* * *</HR>',
					'<p>bar</p></r>'
				]
			],
			[
				[
					'foo',
					'   * * * * *   ',
					'bar'
				],
				[
					'<r><p>foo</p>',
					'<HR>   * * * * *   </HR>',
					'<p>bar</p></r>'
				]
			],
			[
				[
					' - foo',
					'   ***',
					'   bar'
				],
				[
					'<r> <LIST><LI><s>- </s>foo',
					'   ***',
					'   bar</LI></LIST></r>'
				]
			],
			[
				'>  *** ',
				'<r><QUOTE><i>&gt; </i><HR> *** </HR></QUOTE></r>'
			],
			[
				[
					'***',
					'1. one',
					'2. two'
				],
				[
					'<r><HR>***</HR>',
					'<LIST type="decimal"><LI><s>1. </s>one</LI>',
					'<LI><s>2. </s>two</LI></LIST></r>'
				]
			],
			// Inline links
			[
				'Go to [that site](http://example.org) now!',
				'<r><p>Go to <URL url="http://example.org"><s>[</s>that site<e>](http://example.org)</e></URL> now!</p></r>'
			],
			[
				'Go to [that site] (http://example.org) now!',
				'<t><p>Go to [that site] (http://example.org) now!</p></t>'
			],
			[
				'En route to [Mars](http://en.wikipedia.org/wiki/Mars_(disambiguation))!',
				'<r><p>En route to <URL url="http://en.wikipedia.org/wiki/Mars_%28disambiguation%29"><s>[</s>Mars<e>](http://en.wikipedia.org/wiki/Mars_(disambiguation))</e></URL>!</p></r>'
			],
			[
				'Go to [\\[x\\[x\\]x\\]](http://example.org/?foo[]=1&bar\\[\\]=1) now!',
				'<r><p>Go to <URL url="http://example.org/?foo%5B%5D=1&amp;bar%5B%5D=1"><s>[</s>\\[x\\[x\\]x\\]<e>](http://example.org/?foo[]=1&amp;bar\\[\\]=1)</e></URL> now!</p></r>'
			],
			[
				'Check out my [~~lame~~ cool site](http://example.org) now!',
				'<r><p>Check out my <URL url="http://example.org"><s>[</s><DEL><s>~~</s>lame<e>~~</e></DEL> cool site<e>](http://example.org)</e></URL> now!</p></r>'
			],
			[
				'This is [an example](http://example.com/ "Link title") inline link.',
				'<r><p>This is <URL title="Link title" url="http://example.com/"><s>[</s>an example<e>](http://example.com/ "Link title")</e></URL> inline link.</p></r>'
			],
			[
				'This is [an example](http://example.com/ \'Link title\') inline link.',
				'<r><p>This is <URL title="Link title" url="http://example.com/"><s>[</s>an example<e>](http://example.com/ \'Link title\')</e></URL> inline link.</p></r>'
			],
			[
				'This is [an example](http://example.com/ (Link title)) inline link.',
				'<r><p>This is <URL title="Link title" url="http://example.com/"><s>[</s>an example<e>](http://example.com/ (Link title))</e></URL> inline link.</p></r>'
			],
			[
				'This is [an example](http://example.com/ ""Link title"") inline link.',
				'<r><p>This is <URL title="&quot;Link title&quot;" url="http://example.com/"><s>[</s>an example<e>](http://example.com/ ""Link title"")</e></URL> inline link.</p></r>'
			],
			[
				'.. [link](http://example.com/ ")") ..',
				'<r><p>.. <URL title=")" url="http://example.com/"><s>[</s>link<e>](http://example.com/ ")")</e></URL> ..</p></r>'
			],
			[
				'.. [link](http://example.com/ "") ..',
				'<r><p>.. <URL url="http://example.com/"><s>[</s>link<e>](http://example.com/ "")</e></URL> ..</p></r>'
			],
			[
				'.. [link](http://example.com/ "0") ..',
				'<r><p>.. <URL title="0" url="http://example.com/"><s>[</s>link<e>](http://example.com/ "0")</e></URL> ..</p></r>'
			],
			[
				'.. [link](http://example.com/ "Link title") ..',
				'<r><p>.. <URL title="Link title" url="http://example.com/"><s>[</s>link<e>](http://example.com/ "Link title")</e></URL> ..</p></r>'
			],
			[
				".. [link](http://example.com/ 'Link title') ..",
				'<r><p>.. <URL title="Link title" url="http://example.com/"><s>[</s>link<e>](http://example.com/ \'Link title\')</e></URL> ..</p></r>'
			],
			[
				'[not a link]',
				'<t><p>[not a link]</p></t>'
			],
			[
				'.. [..](http://example.org/foo_(bar)) ..',
				'<r><p>.. <URL url="http://example.org/foo_%28bar%29"><s>[</s>..<e>](http://example.org/foo_(bar))</e></URL> ..</p></r>'
			],
			[
				'.. [..](http://example.org/foo_(bar)_baz) ..',
				'<r><p>.. <URL url="http://example.org/foo_%28bar%29_baz"><s>[</s>..<e>](http://example.org/foo_(bar)_baz)</e></URL> ..</p></r>'
			],
			[
				'[b](https://en.wikipedia.org/wiki/B) [b]..[/b]',
				'<r><p><URL url="https://en.wikipedia.org/wiki/B"><s>[</s>b<e>](https://en.wikipedia.org/wiki/B)</e></URL> <STRONG><s>[b]</s>..<e>[/b]</e></STRONG></p></r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B')->tagName = 'STRONG';
				}
			],
			[
				'[](http://example.org/)',
				'<r><p><URL url="http://example.org/"><s>[</s><e>](http://example.org/)</e></URL></p></r>'
			],
			[
				'[[foo]](http://example.org/) [[foo]](http://example.org/)',
				'<r><p><URL url="http://example.org/"><s>[</s>[foo]<e>](http://example.org/)</e></URL> <URL url="http://example.org/"><s>[</s>[foo]<e>](http://example.org/)</e></URL></p></r>'
			],
			[
				'[](http://example.org/?a=1&b=1)[](http://example.org/?a=1&amp;b=1)',
				'<r><p><URL url="http://example.org/?a=1&amp;b=1"><s>[</s><e>](http://example.org/?a=1&amp;b=1)</e></URL><URL url="http://example.org/?a=1&amp;amp;b=1"><s>[</s><e>](http://example.org/?a=1&amp;amp;b=1)</e></URL></p></r>'
			],
			[
				'[](http://example.org/?a=1&b=1)[](http://example.org/?a=1&amp;b=1)',
				'<r><p><URL url="http://example.org/?a=1&amp;b=1"><s>[</s><e>](http://example.org/?a=1&amp;b=1)</e></URL><URL url="http://example.org/?a=1&amp;b=1"><s>[</s><e>](http://example.org/?a=1&amp;amp;b=1)</e></URL></p></r>',
				['decodeHtmlEntities' => true]
			],
			[
				'[x](x "\\a\\b\\\\\\c\\*\\`")',
				'<r><p><URL title="\\a\\b\\\\c*`" url="x"><s>[</s>x<e>](x "\\a\\b\\\\\\c\\*\\`")</e></URL></p></r>'
			],
			[
				'[x](x "foo \\"bar\\"")',
				'<r><p><URL title="foo &quot;bar&quot;" url="x"><s>[</s>x<e>](x "foo \\"bar\\"")</e></URL></p></r>'
			],
			[
				"[x](x 'foo \\'bar\\'')",
				"<r><p><URL title=\"foo 'bar'\" url=\"x\"><s>[</s>x<e>](x 'foo \\'bar\\'')</e></URL></p></r>"
			],
			[
				'[x](x (foo \\(bar\\)))',
				'<r><p><URL title="foo (bar)" url="x"><s>[</s>x<e>](x (foo \\(bar\\)))</e></URL></p></r>'
			],
			[
				[
					'[x](x "a',
					'b")'
				],
				[
					'<r><p><URL title="a&#10;b" url="x"><s>[</s>x<e>](x "a',
					'b")</e></URL></p></r>'
				]
			],
			[
				[
					'> [x](x "a',
					'> b")'
				],
				[
					'<r><QUOTE><i>&gt; </i><p><URL title="a&#10;b" url="x"><s>[</s>x<e>](x "a',
					'&gt; b")</e></URL></p></QUOTE></r>'
				]
			],
			// Reference links
			[
				[
					'[foo][1]',
					'',
					' [1]: http://example.org'
				],
				[
					'<r><p><URL url="http://example.org"><s>[</s>foo<e>][1]</e></URL></p>',
					'',
					'<i> [1]: http://example.org</i></r>'
				]
			],
			[
				[
					'> [foo][1]',
					'>',
					'> [1]: http://example.org'
				],
				[
					'<r><QUOTE><i>&gt; </i><p><URL url="http://example.org"><s>[</s>foo<e>][1]</e></URL></p>',
					'<i>&gt;</i>',
					'<i>&gt; [1]: http://example.org</i></QUOTE></r>'
				]
			],
			[
				[
					'> [foo][1]',
					'>',
					'> [1]: http://example.org',
					'',
					'[foo][1]',
					'',
					'[1]: http://example.com'
				],
				[
					'<r><QUOTE><i>&gt; </i><p><URL url="http://example.org"><s>[</s>foo<e>][1]</e></URL></p>',
					'<i>&gt;</i>',
					'<i>&gt; [1]: http://example.org</i></QUOTE>',
					'',
					'<p><URL url="http://example.org"><s>[</s>foo<e>][1]</e></URL></p>',
					'',
					'<i>[1]: http://example.com</i></r>'
				]
			],
			[
				[
					'[foo][1]',
					'',
					'[1]: http://example.org'
				],
				[
					'<r><p><URL url="http://example.org"><s>[</s>foo<e>][1]</e></URL></p>',
					'',
					'<i>[1]: http://example.org</i></r>'
				]
			],
			[
				[
					'[foo] [1]',
					'',
					'[1]: http://example.org'
				],
				[
					'<r><p><URL url="http://example.org"><s>[</s>foo<e>] [1]</e></URL></p>',
					'',
					'<i>[1]: http://example.org</i></r>'
				]
			],
			[
				[
					'[foo][1]',
					'',
					'[1]: http://example.org "Title goes here"'
				],
				[
					'<r><p><URL title="Title goes here" url="http://example.org"><s>[</s>foo<e>][1]</e></URL></p>',
					'',
					'<i>[1]: http://example.org "Title goes here"</i></r>'
				]
			],
			[
				[
					'[foo][1]',
					'',
					'[1]: http://example.org "\\"Title goes here\\""'
				],
				[
					'<r><p><URL title="&quot;Title goes here&quot;" url="http://example.org"><s>[</s>foo<e>][1]</e></URL></p>',
					'',
					'<i>[1]: http://example.org "\\"Title goes here\\""</i></r>'
				]
			],
			[
				[
					'[foo] bar',
					'',
					'[foo]: http://example.org'
				],
				[
					'<r><p><URL url="http://example.org"><s>[</s>foo<e>]</e></URL> bar</p>',
					'',
					'<i>[foo]: http://example.org</i></r>'
				]
			],
			[
				[
					'[Foo] bar',
					'',
					'[foo]: http://example.org'
				],
				[
					'<r><p><URL url="http://example.org"><s>[</s>Foo<e>]</e></URL> bar</p>',
					'',
					'<i>[foo]: http://example.org</i></r>'
				]
			],
			[
				[
					'[foo] bar',
					'',
					'[Foo]: http://example.org'
				],
				[
					'<r><p><URL url="http://example.org"><s>[</s>foo<e>]</e></URL> bar</p>',
					'',
					'<i>[Foo]: http://example.org</i></r>'
				]
			],
			[
				[
					'[foo] bar',
					'',
					'[foo]: http://example.org',
					'[foo]: http://example.com'
				],
				[
					'<r><p><URL url="http://example.org"><s>[</s>foo<e>]</e></URL> bar</p>',
					'',
					'<i>[foo]: http://example.org',
					'[foo]: http://example.com</i></r>'
				]
			],
			[
				// http://stackoverflow.com/a/20885980
				'[//]: # (This may be the most platform independent comment)',
				'<r><i>[//]: # (This may be the most platform independent comment)</i></r>'
			],
			[
				'[center][text](/url)[/center]',
				'<r><CENTER><s>[center]</s><p><URL url="/url"><s>[</s>text<e>](/url)</e></URL></p><e>[/center]</e></CENTER></r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('center');
				}
			],
			[
				[
					'[foo](/bar)',
					'',
					'[foo]: /baz'
				],
				[
					'<r><p><URL url="/bar"><s>[</s>foo<e>](/bar)</e></URL></p>',
					'',
					'<i>[foo]: /baz</i></r>'
				]
			],
			[
				[
					'[b]bold[/b]',
					'',
					'[b]: /foo'
				],
				[
					'<r><p><B><s>[b]</s>bold<e>[/b]</e></B></p>',
					'',
					'<i>[b]: /foo</i></r>'
				],
				[],
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('b');
				}
			],
			// Images
			[
				'.. ![Alt text](http://example.org/img.png) ..',
				'<r><p>.. <IMG alt="Alt text" src="http://example.org/img.png"><s>![</s>Alt text<e>](http://example.org/img.png)</e></IMG> ..</p></r>'
			],
			[
				'.. ![Alt text](http://example.org/img.png "Image title") ..',
				'<r><p>.. <IMG alt="Alt text" src="http://example.org/img.png" title="Image title"><s>![</s>Alt text<e>](http://example.org/img.png "Image title")</e></IMG> ..</p></r>'
			],
			[
				".. ![Alt text](http://example.org/img.png 'Image title') ..",
				'<r><p>.. <IMG alt="Alt text" src="http://example.org/img.png" title="Image title"><s>![</s>Alt text<e>](http://example.org/img.png \'Image title\')</e></IMG> ..</p></r>'
			],
			[
				'.. ![Alt text](http://example.org/img.png (Image title)) ..',
				'<r><p>.. <IMG alt="Alt text" src="http://example.org/img.png" title="Image title"><s>![</s>Alt text<e>](http://example.org/img.png (Image title))</e></IMG> ..</p></r>'
			],
			[
				'.. ![Alt \\[text\\]](http://example.org/img.png "\\"Image title\\"") ..',
				'<r><p>.. <IMG alt="Alt [text]" src="http://example.org/img.png" title="&quot;Image title&quot;"><s>![</s>Alt \\[text\\]<e>](http://example.org/img.png "\\"Image title\\"")</e></IMG> ..</p></r>'
			],
			[
				'.. ![Alt text](http://example.org/img.png "Image (title)") ..',
				'<r><p>.. <IMG alt="Alt text" src="http://example.org/img.png" title="Image (title)"><s>![</s>Alt text<e>](http://example.org/img.png "Image (title)")</e></IMG> ..</p></r>'
			],
			[
				'.. ![](http://example.org/img.png) ..',
				'<r><p>.. <IMG alt="" src="http://example.org/img.png"><s>![</s><e>](http://example.org/img.png)</e></IMG> ..</p></r>'
			],
			[
				'.. ![[foo]](http://example.org/img.png) ..',
				'<r><p>.. <IMG alt="[foo]" src="http://example.org/img.png"><s>![</s>[foo]<e>](http://example.org/img.png)</e></IMG> ..</p></r>'
			],
			[
				[
					'![alt\\',
					'text](foo.png)'
				],
				[
					'<r><p><IMG alt="alt\\&#10;text" src="foo.png"><s>![</s>alt\\',
					'text<e>](foo.png)</e></IMG></p></r>'
				]
			],
			[
				[
					'![alt](foo.png "line1',
					'line2")'
				],
				[
					'<r><p><IMG alt="alt" src="foo.png" title="line1&#10;line2"><s>![</s>alt<e>](foo.png "line1',
					'line2")</e></IMG></p></r>'
				]
			],
			// Images in links
			[
				'.. [![Alt text](http://example.org/img.png)](http://example.org/) ..',
				'<r><p>.. <URL url="http://example.org/"><s>[</s><IMG alt="Alt text" src="http://example.org/img.png"><s>![</s>Alt text<e>](http://example.org/img.png)</e></IMG><e>](http://example.org/)</e></URL> ..</p></r>'
			],
			// Reference-style images
			[
				[
					'![][1]',
					'',
					'[1]: http://example.org/img.png'
				],
				[
					'<r><p><IMG alt="" src="http://example.org/img.png"><s>![</s><e>][1]</e></IMG></p>',
					'',
					'<i>[1]: http://example.org/img.png</i></r>'
				]
			],
			[
				[
					'![][1] ![][2] ![][1]',
					'',
					'[1]: http://example.org/img.png'
				],
				[
					'<r><p><IMG alt="" src="http://example.org/img.png"><s>![</s><e>][1]</e></IMG> ![][2] <IMG alt="" src="http://example.org/img.png"><s>![</s><e>][1]</e></IMG></p>',
					'',
					'<i>[1]: http://example.org/img.png</i></r>'
				]
			],
			[
				[
					'![][1]',
					'',
					'[1]: http://example.org/img.png "Title goes there"'
				],
				[
					'<r><p><IMG alt="" src="http://example.org/img.png" title="Title goes there"><s>![</s><e>][1]</e></IMG></p>',
					'',
					'<i>[1]: http://example.org/img.png "Title goes there"</i></r>'
				]
			],
			[
				[
					'![][1]',
					'',
					"[1]: http://example.org/img.png 'Title goes there'"
				],
				[
					'<r><p><IMG alt="" src="http://example.org/img.png" title="Title goes there"><s>![</s><e>][1]</e></IMG></p>',
					'',
					"<i>[1]: http://example.org/img.png 'Title goes there'</i></r>"
				]
			],
			[
				[
					'![][1]',
					'',
					"[1]: http://example.org/img.png (Title goes there)"
				],
				[
					'<r><p><IMG alt="" src="http://example.org/img.png" title="Title goes there"><s>![</s><e>][1]</e></IMG></p>',
					'',
					"<i>[1]: http://example.org/img.png (Title goes there)</i></r>"
				]
			],
			[
				[
					'... ![1] ...',
					'',
					'[1]: http://example.org/img.png'
				],
				[
					'<r><p>... <IMG alt="1" src="http://example.org/img.png"><s>![</s>1<e>]</e></IMG> ...</p>',
					'',
					'<i>[1]: http://example.org/img.png</i></r>'
				]
			],
			[
				[
					'... ![1][b][/b] ...',
					'',
					'[1]: http://example.org/img.png'
				],
				[
					'<r><p>... <IMG alt="1" src="http://example.org/img.png"><s>![</s>1<e>]</e></IMG>[b][/b] ...</p>',
					'',
					'<i>[1]: http://example.org/img.png</i></r>'
				]
			],
			[
				[
					'... ![1][b][/b] ...',
					'',
					'[1]: http://example.org/img.png',
					'[b]: http://example.org/b.png'
				],
				[
					'<r><p>... <IMG alt="1" src="http://example.org/b.png"><s>![</s>1<e>][b]</e></IMG>[/b] ...</p>',
					'',
					'<i>[1]: http://example.org/img.png',
					'[b]: http://example.org/b.png</i></r>'
				]
			],
			[
				[
					'![Alt text][1]',
					'',
					'[1]: http://example.org/img.png'
				],
				[
					'<r><p><IMG alt="Alt text" src="http://example.org/img.png"><s>![</s>Alt text<e>][1]</e></IMG></p>',
					'',
					'<i>[1]: http://example.org/img.png</i></r>'
				]
			],
			[
				[
					'[![][1]][1]',
					'',
					'[1]: http://example.org/img.png'
				],
				[
					'<r><p><URL url="http://example.org/img.png"><s>[</s><IMG alt="" src="http://example.org/img.png"><s>![</s><e>][1]</e></IMG><e>][1]</e></URL></p>',
					'',
					'<i>[1]: http://example.org/img.png</i></r>'
				]
			],
			[
				[
					'[![1]][1]',
					'',
					'[1]: http://example.org/img.png'
				],
				[
					'<r><p><URL url="http://example.org/img.png"><s>[</s><IMG alt="1" src="http://example.org/img.png"><s>![</s>1<e>]</e></IMG><e>][1]</e></URL></p>',
					'',
					'<i>[1]: http://example.org/img.png</i></r>'
				]
			],
			// Inline code
			[
				'.. `foo` `bar` ..',
				'<r><p>.. <C><s>`</s>foo<e>`</e></C> <C><s>`</s>bar<e>`</e></C> ..</p></r>'
			],
			[
				'.. `foo `` bar` ..',
				'<r><p>.. <C><s>`</s>foo `` bar<e>`</e></C> ..</p></r>'
			],
			[
				'.. `foo ``` bar` ..',
				'<r><p>.. <C><s>`</s>foo ``` bar<e>`</e></C> ..</p></r>'
			],
			[
				'.. ``foo`` ``bar`` ..',
				'<r><p>.. <C><s>``</s>foo<e>``</e></C> <C><s>``</s>bar<e>``</e></C> ..</p></r>'
			],
			[
				'.. ``foo `bar` baz`` ..',
				'<r><p>.. <C><s>``</s>foo `bar` baz<e>``</e></C> ..</p></r>'
			],
			[
				'`\\`',
				'<r><p><C><s>`</s>\\<e>`</e></C></p></r>'
			],
			[
				'\\``\\`',
				'<r><p>\\`<C><s>`</s>\\<e>`</e></C></p></r>'
			],
			[
				'.. ` x\\`` ` ..',
				'<r><p>.. <C><s>` </s>x\``<e> `</e></C> ..</p></r>'
			],
			[
				'`x` \\` `\\`',
				'<r><p><C><s>`</s>x<e>`</e></C> \\` <C><s>`</s>\\<e>`</e></C></p></r>'
			],
			[
				'.. `[foo](http://example.org)` ..',
				'<r><p>.. <C><s>`</s>[foo](http://example.org)<e>`</e></C> ..</p></r>'
			],
			[
				'.. `![foo](http://example.org)` ..',
				'<r><p>.. <C><s>`</s>![foo](http://example.org)<e>`</e></C> ..</p></r>'
			],
			[
				'.. `x` ..',
				'<r><p>.. <C><s>`</s>x<e>`</e></C> ..</p></r>'
			],
			[
				'.. ``x`` ..',
				'<r><p>.. <C><s>``</s>x<e>``</e></C> ..</p></r>'
			],
			[
				'.. ```x``` ..',
				'<r><p>.. <C><s>```</s>x<e>```</e></C> ..</p></r>'
			],
			[
				"`foo\nbar`",
				"<r><p><C><s>`</s>foo\nbar<e>`</e></C></p></r>"
			],
			[
				"`foo\n\nbar`",
				"<t><p>`foo</p>\n\n<p>bar`</p></t>"
			],
			[
				'```code```',
				'<r><p><C><s>```</s>code<e>```</e></C></p></r>'
			],
			[
				'``` code ```',
				'<r><p><C><s>``` </s>code<e> ```</e></C></p></r>'
			],
			[
				'``` co````de ```',
				'<r><p><C><s>``` </s>co````de<e> ```</e></C></p></r>'
			],
			[
				'``` ```',
				'<r><p><C><s>``` </s><e>```</e></C></p></r>'
			],
			[
				'``` `` ```',
				'<r><p><C><s>``` </s>``<e> ```</e></C></p></r>'
			],
			[
				'` `` `',
				'<r><p><C><s>` </s>``<e> `</e></C></p></r>'
			],
			[
				'``` x ``',
				'<t><p>``` x ``</p></t>'
			],
			[
				'x ``` x ``',
				'<t><p>x ``` x ``</p></t>'
			],
			// Strikethrough
			[
				'.. ~~foo~~ ~~bar~~ ..',
				'<r><p>.. <DEL><s>~~</s>foo<e>~~</e></DEL> <DEL><s>~~</s>bar<e>~~</e></DEL> ..</p></r>'
			],
			[
				'.. ~~foo~bar~~ ..',
				'<r><p>.. <DEL><s>~~</s>foo~bar<e>~~</e></DEL> ..</p></r>'
			],
			[
				'.. ~~foo\\~~ ~~bar~~ ..',
				'<r><p>.. <DEL><s>~~</s>foo\\~~ <e>~~</e></DEL>bar~~ ..</p></r>'
			],
			[
				'.. ~~~~ ..',
				'<t><p>.. ~~~~ ..</p></t>'
			],
			[
				"~~foo\nbar~~",
				"<r><p><DEL><s>~~</s>foo\nbar<e>~~</e></DEL></p></r>"
			],
			[
				"~~foo\n\nbar~~",
				"<t><p>~~foo</p>\n\n<p>bar~~</p></t>"
			],
			// Superscript
			[
				'.. foo^baar^baz 1^2 ..',
				'<r><p>.. foo<SUP><s>^</s>baar<SUP><s>^</s>baz</SUP></SUP> 1<SUP><s>^</s>2</SUP> ..</p></r>'
			],
			[
				'.. \\^_^ ..',
				'<t><p>.. \^_^ ..</p></t>'
			],
			// Emphasis
			[
				'xx ***x*****x** xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG><STRONG><s>**</s>x<e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx ***x****x* xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG><EM><s>*</s>x<e>*</e></EM> xx</p></r>'
			],
			[
				'xx ***x*** xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx ***x**x* xx',
				'<r><p>xx <EM><s>*</s><STRONG><s>**</s>x<e>**</e></STRONG>x<e>*</e></EM> xx</p></r>'
			],
			[
				'xx ***x*x** xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM>x<e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx **x*****x*** xx',
				'<r><p>xx <STRONG><s>**</s>x<e>**</e></STRONG><STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx **x****x** xx',
				'<r><p>xx <STRONG><s>**</s>x<e>**</e></STRONG><STRONG><s>**</s>x<e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx **x***x* xx',
				'<r><p>xx <STRONG><s>**</s>x<e>**</e></STRONG><EM><s>*</s>x<e>*</e></EM> xx</p></r>'
			],
			[
				'xx **x** xx',
				'<r><p>xx <STRONG><s>**</s>x<e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx **x*x** xx',
				'<r><p>xx <STRONG><s>**</s>x*x<e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx *x*****x*** xx',
				'<r><p>xx <EM><s>*</s>x<e>*</e></EM>*<STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx *x****x*** xx',
				'<r><p>xx <EM><s>*</s>x<e>*</e></EM><STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx *x**x* xx',
				'<r><p>xx <EM><s>*</s>x**x<e>*</e></EM> xx</p></r>'
			],
			[
				'xx *x* xx',
				'<r><p>xx <EM><s>*</s>x<e>*</e></EM> xx</p></r>'
			],
			[
				'xx *x**x*x** xx',
				'<r><p>xx <EM><s>*</s>x<STRONG><s>**</s>x</STRONG><e>*</e></EM><STRONG>x<e>**</e></STRONG> xx</p></r>'
			],
			[
				"*foo\nbar*",
				"<r><p><EM><s>*</s>foo\nbar<e>*</e></EM></p></r>"
			],
			[
				"*foo\n\nbar*",
				"<t><p>*foo</p>\n\n<p>bar*</p></t>"
			],
			[
				"***foo*\n\nbar**",
				"<r><p>**<EM><s>*</s>foo<e>*</e></EM></p>\n\n<p>bar**</p></r>"
			],
			[
				"***foo**\n\nbar*",
				"<r><p>*<STRONG><s>**</s>foo<e>**</e></STRONG></p>\n\n<p>bar*</p></r>"
			],
			[
				'xx _x_ xx',
				'<r><p>xx <EM><s>_</s>x<e>_</e></EM> xx</p></r>'
			],
			[
				'xx __x__ xx',
				'<r><p>xx <STRONG><s>__</s>x<e>__</e></STRONG> xx</p></r>'
			],
			[
				'xx foo_bar_baz xx',
				'<t><p>xx foo_bar_baz xx</p></t>'
			],
			[
				'xx foo__bar__baz xx',
				'<r><p>xx foo<STRONG><s>__</s>bar<e>__</e></STRONG>baz xx</p></r>'
			],
			[
				'x _foo_',
				'<r><p>x <EM><s>_</s>foo<e>_</e></EM></p></r>'
			],
			[
				'_foo_ x',
				'<r><p><EM><s>_</s>foo<e>_</e></EM> x</p></r>'
			],
			[
				'_foo_',
				'<r><p><EM><s>_</s>foo<e>_</e></EM></p></r>'
			],
			[
				'xx ***x******x*** xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG><STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx ***x*******x*** xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG>*<STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx *****x***** xx',
				'<r><p>xx **<STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG>** xx</p></r>'
			],
			[
				'xx **x*x*** xx',
				'<r><p>xx <STRONG><s>**</s>x<EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx *x**x*** xx',
				'<r><p>xx <EM><s>*</s>x<STRONG><s>**</s>x<e>**</e></STRONG><e>*</e></EM> xx</p></r>'
			],
			[
				'\\\\*foo*',
				'<r><p>\\\\<EM><s>*</s>foo<e>*</e></EM></p></r>'
			],
			[
				'*\\\\*foo*',
				'<r><p><EM><s>*</s>\\\\<e>*</e></EM>foo*</p></r>'
			],
			[
				'*\\\\*foo*',
				'<r><p><EM><s>*</s>\\\\<e>*</e></EM>foo*</p></r>'
			],
			// Forced line breaks
			[
				[
					'first line  ',
					'second line  ',
					'third line'
				],
				[
					'<t><p>first line  <br/>',
					'second line  <br/>',
					'third line</p></t>'
				],
			],
			[
				[
					'first line  ',
					'second line  '
				],
				[
					'<t><p>first line  <br/>',
					'second line</p>  </t>'
				],
			],
			[
				[
					'> first line  ',
					'> second line  ',
					'',
					'outside quote'
				],
				[
					'<r><QUOTE><i>&gt; </i><p>first line  <br/>',
					'<i>&gt; </i>second line</p>  </QUOTE>',
					'',
					'<p>outside quote</p></r>'
				],
			],
			[
				[
					'    first line  ',
					'    second line  ',
					'',
					'outside code'
				],
				[
					'<r><i>    </i><CODE>first line  ',
					'<i>    </i>second line  </CODE>',
					'',
					'<p>outside code</p></r>'
				],
			],
			[
				[
					'    first line  ',
					'',
					'outside code'
				],
				[
					'<r><i>    </i><CODE>first line  </CODE>',
					'',
					'<p>outside code</p></r>'
				],
			],
			[
				[
					' * first item  ',
					'   still the first item  ',
					' * second item',
					'',
					'outside list'
				],
				[
					'<r> <LIST><LI><s>* </s>first item  <br/>',
					'   still the first item  </LI>',
					' <LI><s>* </s>second item</LI></LIST>',
					'',
					'<p>outside list</p></r>'
				],
			],
			[
				[
					'foo  ',
					'---  ',
					'bar  '
				],
				[
					'<r><H2>foo<e>  ',
					'---  </e></H2>',
					'<p>bar</p>  </r>'
				]
			],
			// Fenced code blocks
			[
				[
					'```',
					'code',
					'```'
				],
				[
					'<r><CODE><s>```</s><i>',
					'</i>code<i>',
					'</i><e>```</e></CODE></r>'
				]
			],
			[
				[
					'~~~',
					'code',
					'~~~'
				],
				[
					'<r><CODE><s>~~~</s><i>',
					'</i>code<i>',
					'</i><e>~~~</e></CODE></r>'
				]
			],
			[
				[
					'~~~',
					'```',
					'code',
					'```',
					'~~~'
				],
				[
					'<r><CODE><s>~~~</s><i>',
					'</i>```',
					'code',
					'```<i>',
					'</i><e>~~~</e></CODE></r>'
				]
			],
			[
				[
					'```php',
					'code',
					'```'
				],
				[
					'<r><CODE lang="php"><s>```php</s><i>',
					'</i>code<i>',
					'</i><e>```</e></CODE></r>'
				]
			],
			[
				[
					'``` php ',
					'code',
					'```'
				],
				[
					'<r><CODE lang="php"><s>``` php </s><i>',
					'</i>code<i>',
					'</i><e>```</e></CODE></r>'
				]
			],
			[
				[
					'> ```',
					'> code',
					'> ```'
				],
				[
					'<r><QUOTE><i>&gt; </i><CODE><s>```</s><i>',
					'&gt; </i>code<i>',
					'&gt; </i><e>```</e></CODE></QUOTE></r>'
				]
			],
			[
				[
					'    code',
					'```',
					'more code',
					'```'
				],
				[
					'<r><i>    </i><CODE>code</CODE>',
					'<CODE><s>```</s><i>',
					'</i>more code<i>',
					'</i><e>```</e></CODE></r>'
				]
			],
			[
				[
					'> *x* ',
					'',
					'```',
					'```',
					'',
					'***'
				],
				[
					'<r><QUOTE><i>&gt; </i><p><EM><s>*</s>x<e>*</e></EM></p> </QUOTE>',
					'',
					'<CODE><s>```</s><i>',
					'</i><e>```</e></CODE>',
					'',
					'<HR>***</HR></r>'
				]
			],
			[
				[
					'```',
					'    code',
					'```'
				],
				[
					'<r><CODE><s>```</s><i>',
					'</i>    code<i>',
					'</i><e>```</e></CODE></r>'
				]
			],
			[
				[
					'```',
					'	code',
					'```'
				],
				[
					'<r><CODE><s>```</s><i>',
					'</i>	code<i>',
					'</i><e>```</e></CODE></r>'
				]
			],
			[
				[
					'> ```',
					'> 	code',
					'> ```'
				],
				[
					'<r><QUOTE><i>&gt; </i><CODE><s>```</s><i>',
					'&gt; </i>	code<i>',
					'&gt; </i><e>```</e></CODE></QUOTE></r>'
				]
			],
			[
				[
					'```',
					'> code',
					'> code',
					'```'
				],
				[
					'<r><CODE><s>```</s><i>',
					'</i>&gt; code',
					'&gt; code<i>',
					'</i><e>```</e></CODE></r>'
				]
			],
			[
				[
					'> ```',
					'> > code',
					'> > code',
					'> ```'
				],
				[
					'<r><QUOTE><i>&gt; </i><CODE><s>```</s><i>',
					'&gt; </i>&gt; code',
					'<i>&gt; </i>&gt; code<i>',
					'&gt; </i><e>```</e></CODE></QUOTE></r>'
				]
			],
			[
				[
					'````foo',
					'```',
					'...',
					'```',
					'````'
				],
				[
					'<r><CODE lang="foo"><s>````foo</s><i>',
					'</i>```',
					'...',
					'```<i>',
					'</i><e>````</e></CODE></r>'
				]
			],
		]);
	}

	public function getRenderingTests()
	{
		return self::fixTests([
			[
				'> foo',
				'<blockquote><p>foo</p></blockquote>'
			],
			[
				[
					'> > foo',
					'> ',
					'> bar',
					'',
					'baz'
				],
				[
					'<blockquote><blockquote><p>foo</p></blockquote>',
					'',
					'<p>bar</p></blockquote>',
					'',
					'<p>baz</p>'
				]
			],
			[
				[
					'foo',
					'',
					'## bar',
					'',
					'baz'
				],
				[
					'<p>foo</p>',
					'',
					'<h2>bar</h2>',
					'',
					'<p>baz</p>'
				]
			],
			[
				[
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
				],
				[
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
				]
			],
			[
				[
					'1. one',
					'2. two'
				],
				[
					'<ol><li>one</li>',
					'<li>two</li></ol>'
				]
			],
			[
				[
					' 21. twenty-one',
					' 22. twenty-two'
				],
				[
					' <ol start="21"><li>twenty-one</li>',
					' <li>twenty-two</li></ol>'
				]
			],
			[
				[
					'- one',
					'  - foo',
					'  - bar',
					'',
					'- two',
					'  - bar',
					'  - baz',
					'',
					'- three'
				],
				[
					'<ul><li><p>one</p>',
					'  <ul><li>foo</li>',
					'  <li>bar</li></ul></li>',
					'',
					'<li><p>two</p>',
					'  <ul><li>bar</li>',
					'  <li>baz</li></ul></li>',
					'',
					'<li><p>three</p></li></ul>'
				],
			],
			[
				'[Link text](http://example.org)',
				'<p><a href="http://example.org">Link text</a></p>'
			],
			[
				'[Link text](http://example.org "Link title")',
				'<p><a href="http://example.org" title="Link title">Link text</a></p>'
			],
			[
				[
					'```',
					'code',
					'```'
				],
				'<pre><code>code</code></pre>'
			],
			[
				[
					'```html',
					'code',
					'```'
				],
				'<pre><code class="language-html">code</code></pre>'
			],
			[
				[
					'![alt',
					'text](img)'
				],
				[
					'<p><img src="img" alt="alt',
					'text"></p>'
				]
			],
		]);
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
				$test[2] = [];
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