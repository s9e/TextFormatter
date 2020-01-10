<?php

namespace s9e\TextFormatter\Tests\Plugins\HTMLEntities;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\HTMLEntities\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\HTMLEntities\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return [
			[
				'AT&amp;T',
				'<r>AT<HE char="&amp;">&amp;amp;</HE>T</r>'
			],
			[
				'AT&amp;T',
				'<r>AT<FOO char="&amp;">&amp;amp;</FOO>T</r>',
				['tagName' => 'FOO']
			],
			[
				'AT&amp;T',
				'<r>AT<HE bar="&amp;">&amp;amp;</HE>T</r>',
				['attrName' => 'bar']
			],
			[
				'I &hearts; AT&amp;T',
				'<r>I <HE char="♥">&amp;hearts;</HE> AT<HE char="&amp;">&amp;amp;</HE>T</r>'
			],
			[
				'I &#x2665; AT&amp;T',
				'<r>I <HE char="♥">&amp;#x2665;</HE> AT<HE char="&amp;">&amp;amp;</HE>T</r>'
			],
			[
				'I &#9829; AT&amp;T',
				'<r>I <HE char="♥">&amp;#9829;</HE> AT<HE char="&amp;">&amp;amp;</HE>T</r>'
			],
			[
				'Some &unknown; entity',
				'<t>Some &amp;unknown; entity</t>'
			],
			[
				'&#00;&#32;',
				'<r>&amp;#00;<HE char=" ">&amp;#32;</HE></r>'
			],
			[
				'&#00;&#32;',
				'<r>&amp;#00;<HE char=" ">&amp;#32;</HE></r>'
			],
			[
				'&Hat;',
				'<r><HE char="^">&amp;Hat;</HE></r>'
			],
		];
	}

	public function getRenderingTests()
	{
		return [
			[
				'AT&amp;T',
				'AT&amp;T',
			],
			[
				'AT&amp;T',
				'AT&amp;T',
				['tagName' => 'FOO']
			],
			[
				'AT&amp;T',
				'AT&amp;T',
				['attrName' => 'bar']
			],
			[
				'I &hearts; AT&amp;T',
				'I ♥ AT&amp;T'
			],
			[
				'Pok&eacute;man',
				'Pokéman'
			],
			[
				'POK&Eacute;MAN',
				'POKÉMAN'
			],
		];
	}
}