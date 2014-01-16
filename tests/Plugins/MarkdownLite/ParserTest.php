<?php

namespace s9e\TextFormatter\Tests\Plugins\MarkdownLite;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\MarkdownLite\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\MarkdownLite\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
//	use RenderingTestsRunner;

	public function getParsingTests()
	{
		$tests = [
			// Links
			[
				'Go to [that site](http://example.org) now!',
				'<r><p>Go to <URL url="http://example.org"><s>[</s>that site<e>](http://example.org)</e></URL> now!</p></r>'
			],
			[
				'Go to [that site] (http://example.org) now!',
				'<r><p>Go to <URL url="http://example.org"><s>[</s>that site<e>] (http://example.org)</e></URL> now!</p></r>'
			],
			[
				'En route to [Mars](http://en.wikipedia.org/wiki/Mars_(disambiguation\))!',
				'<r><p>En route to <URL url="http://en.wikipedia.org/wiki/Mars_%28disambiguation%29"><s>[</s>Mars<e>](http://en.wikipedia.org/wiki/Mars_(disambiguation\))</e></URL>!</p></r>'
			],
			[
				'Go to [\\[x\\[x\\]x\\]](http://example.org/?foo[]=1&bar\\[\\]=1) now!',
				'<r><p>Go to <URL url="http://example.org/?foo%5B%5D=1&amp;bar%5B%5D=1"><s>[</s>\\[x\\[x\\]x\\]<e>](http://example.org/?foo[]=1&amp;bar\\[\\]=1)</e></URL> now!</p></r>'
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
				'.. ![Alt \\[text\\]](http://example.org/img.png "\\"Image title\\"") ..',
				'<r><p>.. <IMG alt="Alt [text]" src="http://example.org/img.png" title="&quot;Image title&quot;"><s>![</s>Alt \\[text\\]<e>](http://example.org/img.png "\\"Image title\\"")</e></IMG> ..</p></r>'
			],
			[
				'.. ![Alt text](http://example.org/img.png "Image (title)") ..',
				'<r><p>.. <IMG alt="Alt text" src="http://example.org/img.png" title="Image (title)"><s>![</s>Alt text<e>](http://example.org/img.png "Image (title)")</e></IMG> ..</p></r>'
			],
			// Images in links
			[
				'.. [![Alt text](http://example.org/img.png)](http://example.org/) ..',
				'<r><p>.. <URL url="http://example.org/"><s>[</s><IMG alt="Alt text" src="http://example.org/img.png"><s>![</s>Alt text<e>](http://example.org/img.png)</e></IMG><e>](http://example.org/)</e></URL> ..</p></r>'
			],
			// Inline code
			[
				'.. `foo` `bar` ..',
				'<r><p>.. <C><s>`</s>foo<e>`</e></C> <C><s>`</s>bar<e>`</e></C> ..</p></r>'
			],
			[
				'.. `foo\\` \\`b\\\\ar` ..',
				'<r><p>.. <C><s>`</s>foo\\` \\`b\\\\ar<e>`</e></C> ..</p></r>'
			],
			[
				'.. `[foo](http://example.org)` ..',
				'<r><p>.. <C><s>`</s>[foo](http://example.org)<e>`</e></C> ..</p></r>'
			],
			[
				'.. `![foo](http://example.org)` ..',
				'<r><p>.. <C><s>`</s>![foo](http://example.org)<e>`</e></C> ..</p></r>'
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
			// Superscript
			[
				'.. foo^baar^baz 1^2 ..',
				'<r><p>.. foo<SUP><s>^</s>baar<SUP><s>^</s>baz</SUP></SUP> 1<SUP><s>^</s>2</SUP> ..</p></r>'
			],
			[
				'.. \\^_^ ..',
				'<t><p>.. \^_^ ..</p></t>'
			],
		];

		foreach ($tests as &$test)
		{
			$test[] = [];
			$test[] = function ($configurator)
			{
				$configurator->addHTML5Rules();
			};
		}

		return $tests;
	}
}