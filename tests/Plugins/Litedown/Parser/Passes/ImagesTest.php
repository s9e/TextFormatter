<?php

namespace s9e\TextFormatter\Tests\Plugins\Litedown\Parser\Passes;

/**
* @covers s9e\TextFormatter\Plugins\Litedown\Parser
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\LinkAttributesSetter;
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\ParsedText
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\AbstractPass
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\Images
*/
class ImagesTest extends AbstractTest
{
	public function getParsingTests()
	{
		return self::fixTests([
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
			[
				'![]( http://example.org/img.png "Title" )',
				'<r><p><IMG alt="" src="http://example.org/img.png" title="Title"><s>![</s><e>]( http://example.org/img.png "Title" )</e></IMG></p></r>'
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
		]);
	}

	public function getRenderingTests()
	{
		return self::fixTests([
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
}