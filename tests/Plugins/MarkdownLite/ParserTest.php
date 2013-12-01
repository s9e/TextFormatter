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
				'<rt><p>Go to <URL url="http://example.org"><s>[</s>that site<e>](http://example.org)</e></URL> now!</p></rt>'
			],
			[
				'Go to [that site] (http://example.org) now!',
				'<rt><p>Go to <URL url="http://example.org"><s>[</s>that site<e>] (http://example.org)</e></URL> now!</p></rt>'
			],
			[
				'En route to [Mars](http://en.wikipedia.org/wiki/Mars_(disambiguation\))!',
				'<rt><p>En route to <URL url="http://en.wikipedia.org/wiki/Mars_%28disambiguation%29"><s>[</s>Mars<e>](http://en.wikipedia.org/wiki/Mars_(disambiguation\))</e></URL>!</p></rt>'
			],
			[
				'Go to [\\[x\\[x\\]x\\]](http://example.org/?foo[]=1&bar\\[\\]=1) now!',
				'<rt><p>Go to <URL url="http://example.org/?foo%5B%5D=1&amp;bar%5B%5D=1"><s>[</s>\\[x\\[x\\]x\\]<e>](http://example.org/?foo[]=1&amp;bar\\[\\]=1)</e></URL> now!</p></rt>'
			],
			// Images
			[
				'.. ![Alt text](http://example.org/img.png) ..',
				'<rt><p>.. <IMG alt="Alt text" src="http://example.org/img.png"><s>![</s>Alt text<e>](http://example.org/img.png)</e></IMG> ..</p></rt>'
			],
			[
				'.. ![Alt text](http://example.org/img.png "Image title") ..',
				'<rt><p>.. <IMG alt="Alt text" src="http://example.org/img.png" title="Image title"><s>![</s>Alt text<e>](http://example.org/img.png "Image title")</e></IMG> ..</p></rt>'
			],
			[
				'.. ![Alt \\[text\\]](http://example.org/img.png "\\"Image title\\"") ..',
				'<rt><p>.. <IMG alt="Alt [text]" src="http://example.org/img.png" title="&quot;Image title&quot;"><s>![</s>Alt \\[text\\]<e>](http://example.org/img.png "\\"Image title\\"")</e></IMG> ..</p></rt>'
			],
			[
				'.. ![Alt text](http://example.org/img.png "Image (title)") ..',
				'<rt><p>.. <IMG alt="Alt text" src="http://example.org/img.png" title="Image (title)"><s>![</s>Alt text<e>](http://example.org/img.png "Image (title)")</e></IMG> ..</p></rt>'
			],
			// Images in links
			[
				'.. [![Alt text](http://example.org/img.png)](http://example.org/) ..',
				'<rt><p>.. <URL url="http://example.org/"><s>[</s><IMG alt="Alt text" src="http://example.org/img.png"><s>![</s>Alt text<e>](http://example.org/img.png)</e></IMG><e>](http://example.org/)</e></URL> ..</p></rt>'
			],
			// Inline code
			[
				'.. `foo` `bar` ..',
				'<rt><p>.. <C><s>`</s>foo<e>`</e></C> <C><s>`</s>bar<e>`</e></C> ..</p></rt>'
			],
			[
				'.. `foo\\` \\`b\\\\ar` ..',
				'<rt><p>.. <C><s>`</s>foo\\` \\`b\\\\ar<e>`</e></C> ..</p></rt>'
			],
			[
				'.. `[foo](http://example.org)` ..',
				'<rt><p>.. <C><s>`</s>[foo](http://example.org)<e>`</e></C> ..</p></rt>'
			],
			[
				'.. `![foo](http://example.org)` ..',
				'<rt><p>.. <C><s>`</s>![foo](http://example.org)<e>`</e></C> ..</p></rt>'
			],
			//Strikethrough
			[
				'.. ~~foo~~ ~~bar~~ ..',
				'<rt><p>.. <DEL><s>~~</s>foo<e>~~</e></DEL> <DEL><s>~~</s>bar<e>~~</e></DEL> ..</p></rt>'
			],
			[
				'.. ~~foo~bar~~ ..',
				'<rt><p>.. <DEL><s>~~</s>foo~bar<e>~~</e></DEL> ..</p></rt>'
			],
			[
				'.. ~~foo\\~~ ~~bar~~ ..',
				'<rt><p>.. <DEL><s>~~</s>foo\\~~ <e>~~</e></DEL>bar~~ ..</p></rt>'
			],
			[
				'.. ~~~~ ..',
				'<pt><p>.. ~~~~ ..</p></pt>'
			],
			// Superscript
			[
				'.. foo^baar^baz 1^2 ..',
				'<rt><p>.. foo<SUP><s>^</s>baar<SUP><s>^</s>baz</SUP></SUP> 1<SUP><s>^</s>2</SUP> ..</p></rt>'
			],
			[
				'.. \\^_^ ..',
				'<pt><p>.. \^_^ ..</p></pt>'
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