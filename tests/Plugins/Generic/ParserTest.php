<?php

namespace s9e\TextFormatter\Tests\Plugins\Generic;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Generic\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Generic\Parser
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
				'<r>Follow <GAC9F10E2 username="twitter">@twitter</GAC9F10E2> for more info</r>',
				[],
				function ($configurator)
				{
					$configurator->Generic->add(
						'/@(?<username>[a-z0-9_]{1,15})/i',
						'<a href="https://twitter.com/{@username}"><xsl:apply-templates/></a>'
					);
				}
			],
			[
				'Some *emphasis*.',
				'<r>Some <G86655032><s>*</s>emphasis<e>*</e></G86655032>.</r>',
				[],
				function ($configurator)
				{
					$configurator->Generic->add(
						'/\\*(.*?)\\*/',
						'<em>$1</em>'
					);
				}
			],
			[
				'Markdown [link](http://example.com) style.',
				'<r>Markdown <G792685FB _2="http://example.com"><s>[</s>link<e>](http://example.com)</e></G792685FB> style.</r>',
				[],
				function ($configurator)
				{
					$configurator->Generic->add(
						'#\\[(.*?)\\]\\((https?://.*?)\\)#i',
						'<a href="$2">$1</a>'
					);
				}
			],
			[
				'Some *_bold_ emphasis* or _*emphasised* boldness_.',
				'<r>Some <G86655032><s>*</s><G74E475F4><s>_</s>bold<e>_</e></G74E475F4> emphasis<e>*</e></G86655032> or <G74E475F4><s>_</s><G86655032><s>*</s>emphasised<e>*</e></G86655032> boldness<e>_</e></G74E475F4>.</r>',
				[],
				function ($configurator)
				{
					$configurator->Generic->add(
						'/\\*(.*?)\\*/',
						'<em>$1</em>'
					);
					$configurator->Generic->add(
						'/_(.*?)_/',
						'<b>$1</b>'
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
					$configurator->Generic->add(
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
					$configurator->Generic->add(
						'/\\*(.*?)\\*/',
						'<em>$1</em>'
					);
				}
			],
			[
				'Markdown [link](http://example.com) style.',
				'Markdown <a href="http://example.com">link</a> style.',
				[],
				function ($configurator)
				{
					$configurator->Generic->add(
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
					$configurator->Generic->add(
						'/\\*(.*?)\\*/',
						'<em>$1</em>'
					);
					$configurator->Generic->add(
						'/_(.*?)_/',
						'<b>$1</b>'
					);
				}
			],
		];
	}
}