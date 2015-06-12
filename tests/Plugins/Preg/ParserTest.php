<?php

namespace s9e\TextFormatter\Tests\Plugins\Preg;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Preg\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Preg\Parser
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
				'Follow @twitter for more info',
				'<r>Follow <PREG_AC9F10E2 username="twitter">@twitter</PREG_AC9F10E2> for more info</r>',
				[],
				function ($configurator)
				{
					$configurator->Preg->replace(
						'/@(?<username>[a-z0-9_]{1,15})/i',
						'<a href="https://twitter.com/{@username}"><xsl:apply-templates/></a>'
					);
				}
			],
			[
				'Some *emphasis*.',
				'<r>Some <PREG_86655032><s>*</s>emphasis<e>*</e></PREG_86655032>.</r>',
				[],
				function ($configurator)
				{
					$configurator->Preg->replace('/\\*(.*?)\\*/', '<em>$1</em>');
				}
			],
			[
				'Markdown [link](http://example.com) style.',
				'<r>Markdown <PREG_792685FB _2="http://example.com"><s>[</s>link<e>](http://example.com)</e></PREG_792685FB> style.</r>',
				[],
				function ($configurator)
				{
					$configurator->Preg->replace(
						'#\\[(.*?)\\]\\((https?://.*?)\\)#i',
						'<a href="$2">$1</a>'
					);
				}
			],
			[
				'Some *_bold_ emphasis* or _*emphasised* boldness_.',
				'<r>Some <PREG_86655032><s>*</s><PREG_74E475F4><s>_</s>bold<e>_</e></PREG_74E475F4> emphasis<e>*</e></PREG_86655032> or <PREG_74E475F4><s>_</s><PREG_86655032><s>*</s>emphasised<e>*</e></PREG_86655032> boldness<e>_</e></PREG_74E475F4>.</r>',
				[],
				function ($configurator)
				{
					$configurator->Preg->replace('/\\*(.*?)\\*/', '<em>$1</em>');
					$configurator->Preg->replace('/_(.*?)_/',     '<b>$1</b>');
				}
			],
			[
				'@foo @"bar"',
				'<r><PREG_979965EC name="foo">@foo</PREG_979965EC> <PREG_979965EC name="bar">@"bar"</PREG_979965EC></r>',
				[],
				function ($configurator)
				{
					$configurator->Preg->replace(
						'/(?J)@(?:(?<name>\\w+)|"(?<name>[^"]+)")/',
						'<cite><xsl:value-of select="@name"/></cite>'
					);
				}
			],
			[
				'foo bar',
				'<r><PREG_2467EF05 name="foo">foo</PREG_2467EF05> <PREG_2467EF05 name="bar">bar</PREG_2467EF05></r>',
				[],
				function ($configurator)
				{
					$configurator->Preg->replace(
						'/(?J)(?<name>foo)|(?<name>bar)/',
						'<cite><xsl:value-of select="@name"/></cite>'
					);
				}
			],
		];
	}

	public function getRenderingTests()
	{
		return [
			[
				'Follow @twitter for more info',
				'Follow <a href="https://twitter.com/twitter">@twitter</a> for more info',
				[],
				function ($configurator)
				{
					$configurator->Preg->replace(
						'/@(?<username>[a-z0-9_]{1,15})/i',
						'<a href="https://twitter.com/{@username}"><xsl:apply-templates/></a>'
					);
				}
			],
			[
				'Some *emphasis*.',
				'Some <em>emphasis</em>.',
				[],
				function ($configurator)
				{
					$configurator->Preg->replace('/\\*(.*?)\\*/', '<em>$1</em>');
				}
			],
			[
				'Markdown [link](http://example.com) style.',
				'Markdown <a href="http://example.com">link</a> style.',
				[],
				function ($configurator)
				{
					$configurator->Preg->replace(
						'#\\[(.*?)\\]\\((https?://.*?)\\)#i',
						'<a href="$2">$1</a>'
					);
				}
			],
			[
				'Some *_bold_ emphasis* or _*emphasised* boldness_.',
				'Some <em><b>bold</b> emphasis</em> or <b><em>emphasised</em> boldness</b>.',
				[],
				function ($configurator)
				{
					$configurator->Preg->replace('/\\*(.*?)\\*/', '<em>$1</em>');
					$configurator->Preg->replace('/_(.*?)_/',     '<b>$1</b>');
				}
			],
		];
	}
}