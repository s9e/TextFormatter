<?php

namespace s9e\TextFormatter\Tests\Plugins\HTMLComments;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\HTMLComments\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\HTMLComments\Parser
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
				'x<!--foo-->y',
				'<r>x<HC content="foo">&lt;!--foo--&gt;</HC>y</r>'
			],
			[
				'x<!--foo-->y',
				'<r>x<FOO content="foo">&lt;!--foo--&gt;</FOO>y</r>',
				['tagName' => 'FOO']
			],
			[
				'x<!--foo-->y',
				'<r>x<HC foo="foo">&lt;!--foo--&gt;</HC>y</r>',
				['attrName' => 'foo']
			],
			[
				'x<!--[foo]-->y',
				'<r>x<HC content="[foo]">&lt;!--[foo]--&gt;</HC>y</r>'
			],
			[
				'x<!--[if IE]-->y',
				'<t>x&lt;!--[if IE]--&gt;y</t>'
			],
			[
				'x<!--foo--bar-->y',
				'<r>x<HC content="foobar">&lt;!--foo--bar--&gt;</HC>y</r>'
			],
			[
				'x<!--foo<bar-->y',
				'<r>x<HC content="foobar">&lt;!--foo&lt;bar--&gt;</HC>y</r>'
			],
			[
				'x<!--foo>bar-->y',
				'<r>x<HC content="foobar">&lt;!--foo&gt;bar--&gt;</HC>y</r>'
			],
			[
				'x<!--foo&gt;bar-->y',
				'<r>x<HC content="foobar">&lt;!--foo&amp;gt;bar--&gt;</HC>y</r>'
			],
			[
				'<!--foo--->',
				'<r><HC content="foo">&lt;!--foo---&gt;</HC></r>'
			],
		];
	}

	public function getRenderingTests()
	{
		return [
			[
				'x<!--foo-->y',
				'x<!--foo-->y'
			],
			[
				'x<!--foo-->y',
				'x<!--foo-->y',
				['tagName' => 'FOO']
			],
			[
				'x<!--foo-->y',
				'x<!--foo-->y',
				['attrName' => 'FOO']
			],
			[
				'x<!--foo--bar-->y',
				'x<!--foobar-->y'
			],
			[
				'x<!--foo<bar-->y',
				'x<!--foobar-->y'
			],
			[
				'x<!--foo>bar-->y',
				'x<!--foobar-->y'
			],
			[
				'x<!--foo-<>-bar-->y',
				'x<!--foobar-->y'
			],
			[
				'x<!--foo&gt;bar-->y',
				'x<!--foobar-->y'
			],
		];
	}
}