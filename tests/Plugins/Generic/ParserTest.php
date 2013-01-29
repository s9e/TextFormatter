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
				'<rt>Follow <GAC9F10E2 username="twitter">@twitter</GAC9F10E2> for more info</rt>',
				[],
				function ($constructor)
				{
					$constructor->Generic->add(
						'/@(?<username>[a-z0-9_]{1,15})/i',
						'<a href="https://twitter.com/{@username}"><xsl:apply-templates/></a>'
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
				function ($constructor)
				{
					$constructor->Generic->add(
						'/@(?<username>[a-z0-9_]{1,15})/i',
						'<a href="https://twitter.com/{@username}"><xsl:apply-templates/></a>'
					);
				}
			],
		];
	}
}