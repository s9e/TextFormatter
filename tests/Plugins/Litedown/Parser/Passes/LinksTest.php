<?php

namespace s9e\TextFormatter\Tests\Plugins\Litedown\Parser\Passes;

/**
* @covers s9e\TextFormatter\Plugins\Litedown\Parser
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\ParsedText
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\AbstractPass
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\Links
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\LinkReferences
*/
class LinksTest extends AbstractTest
{
	public function getParsingTests()
	{
		return self::fixTests([
			[
				'',
				'<t></t>',
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
			[
				'[..]( http://example.org )',
				'<r><p><URL url="http://example.org"><s>[</s>..<e>]( http://example.org )</e></URL></p></r>'
			],
			// Automatic links
			[
				'.. <https://example.org> ..',
				'<r><p>.. <URL url="https://example.org"><s>&lt;</s>https://example.org<e>&gt;</e></URL> ..</p></r>'
			],
			[
				'.. <https://example.org/&#20;/> ..',
				'<r><p>.. <URL url="https://example.org/&amp;#20;/"><s>&lt;</s>https://example.org/&amp;#20;/<e>&gt;</e></URL> ..</p></r>'
			],
			[
				'<https://example.org/\[\>',
				'<r><p><URL url="https://example.org/%5C%5B%5C"><s>&lt;</s>https://example.org/\[\<e>&gt;</e></URL></p></r>'
			],
			[
				'<mailto:user@example.org>',
				'<r><p><URL url="mailto:user@example.org"><s>&lt;</s>mailto:user@example.org<e>&gt;</e></URL></p></r>',
				[],
				function ($configurator)
				{
					$configurator->urlConfig->allowScheme('mailto');
				}
			],
			[
				'<user@example.org>',
				'<r><p><EMAIL email="user@example.org"><s>&lt;</s>user@example.org<e>&gt;</e></EMAIL></p></r>'
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
			[
				[
					'[quote]foo[/quote]',
					'',
					'[quote]:foo:[/quote]'
				],
				[
					'<r><QUOTE><s>[quote]</s><p>foo</p><e>[/quote]</e></QUOTE>',
					'',
					'<QUOTE><s>[quote]</s><p>:foo:</p><e>[/quote]</e></QUOTE></r>'
				],
				[],
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('QUOTE');
				}
			],
			[
				[
					'[foo][1]',
					'',
					'[1]:  http://example.org  "Title" '
				],
				[
					'<r><p><URL title="Title" url="http://example.org"><s>[</s>foo<e>][1]</e></URL></p>',
					'',
					'<i>[1]:  http://example.org  "Title" </i></r>'
				]
			],
			[
				[
					'[foo][1]',
					'',
					'[1]: http://example.org/?x\\[1\\]=2'
				],
				[
					'<r><p><URL url="http://example.org/?x%5B1%5D=2"><s>[</s>foo<e>][1]</e></URL></p>',
					'',
					'<i>[1]: http://example.org/?x\\[1\\]=2</i></r>'
				]
			],
		]);
	}

	public function getRenderingTests()
	{
		return [
			[
				'[Link text](http://example.org)',
				'<p><a href="http://example.org">Link text</a></p>'
			],
			[
				'[Link text](http://example.org "Link title")',
				'<p><a href="http://example.org" title="Link title">Link text</a></p>'
			],
			[
				'.. <https://example.org/&#20;/> ..',
				'<p>.. <a href="https://example.org/&amp;#20;/">https://example.org/&amp;#20;/</a> ..</p>'
			],
		];
	}
}