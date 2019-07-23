<?php

namespace s9e\TextFormatter\Tests\Plugins\Litedown\Parser\Passes;

/**
* @covers s9e\TextFormatter\Plugins\Litedown\Parser
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\ParsedText
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\AbstractPass
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\Blocks
*/
class BlocksTest extends AbstractTest
{
	public function getParsingTests()
	{
		return self::fixTests([
			[
				'',
				'<t></t>',
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
			// Block spoilers
			[
				'>! ...',
				'<r><SPOILER><i>&gt;! </i><p>...</p></SPOILER></r>'
			],
			[
				'>! >! ...',
				'<r><SPOILER><SPOILER><i>&gt;! &gt;! </i><p>...</p></SPOILER></SPOILER></r>'
			],
			[
				[
					'>! >! foo',
					'>!',
					'>! bar',
					'',
					'baz'
				],
				[
					'<r><SPOILER><SPOILER><i>&gt;! &gt;! </i><p>foo</p></SPOILER>',
					'<i>&gt;!</i>',
					'<i>&gt;! </i><p>bar</p></SPOILER>',
					'',
					'<p>baz</p></r>'
				]
			],
			[
				[
					'> >! foo',
					'>',
					'> bar',
					'',
					'baz'
				],
				[
					'<r><QUOTE><SPOILER><i>&gt; &gt;! </i><p>foo</p></SPOILER>',
					'<i>&gt;</i>',
					'<i>&gt; </i><p>bar</p></QUOTE>',
					'',
					'<p>baz</p></r>'
				]
			],
			[
				[
					'> foo',
					'',
					'',
					'>! bar'
				],
				[
					'<r><QUOTE><i>&gt; </i><p>foo</p></QUOTE>',
					'',
					'',
					'<SPOILER><i>&gt;! </i><p>bar</p></SPOILER></r>'
				]
			],
			[
				'> >! ...',
				'<r><QUOTE><SPOILER><i>&gt; &gt;! </i><p>...</p></SPOILER></QUOTE></r>'
			],
			[
				'>! >! ... !<',
				'<r><SPOILER><i>&gt;! </i><p><ISPOILER><s>&gt;!</s> ... <e>!&lt;</e></ISPOILER></p></SPOILER></r>'
			],
			[
				// https://stackoverflow.com/editing-help#link-spoilers
				[
					'At the end of episode five, it turns out that',
					">! he's actually his father."
				],
				[
					'<r><p>At the end of episode five, it turns out that</p>',
					"<SPOILER><i>&gt;! </i><p>he's actually his father.</p></SPOILER></r>"
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
				"\t",
				"<r><i>\t</i></r>"
			],
			[
				[
					'x',
					'',
					"\t",
					'',
					'x'
				],
				[
					'<r><p>x</p>',
					'',
					"<i>\t</i>",
					'',
					'<p>x</p></r>'
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
					' * foo',
					'',
					'   * bar',
					'',
					'     baz'
				],
				[
					'<r> <LIST><LI><s>* </s><p>foo</p>',
					'',
					'   <LIST><LI><s>* </s><p>bar</p>',
					'',
					'     <p>baz</p></LI></LIST></LI></LIST></r>'
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
			[
				[
					'List:',
					'- first',
					'- second'
				],
				[
					'<r><p>List:</p>',
					'<LIST><LI><s>- </s>first</LI>',
					'<LI><s>- </s>second</LI></LIST></r>'
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
			[
				[
					'>! foo',
					'>! ---'
				],
				[
					'<r><SPOILER><i>&gt;! </i><H2>foo<e>',
					'&gt;! ---</e></H2></SPOILER></r>'
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
					'>! ```',
					'>! code',
					'>! ```'
				],
				[
					'<r><SPOILER><i>&gt;! </i><CODE><s>```</s><i>',
					'&gt;! </i>code<i>',
					'&gt;! </i><e>```</e></CODE></SPOILER></r>'
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
			[
				[
					'```',
					'```'
				],
				[
					'<r><CODE><s>```</s><i>',
					'</i><e>```</e></CODE></r>'
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
		]);
	}
}