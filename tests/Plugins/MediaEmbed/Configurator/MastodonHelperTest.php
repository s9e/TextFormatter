<?php declare(strict_types=1);

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed\Configurator;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\MastodonHelper;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\MastodonHelper
*/
class MastodonHelperTest extends Test
{
	/**
	* @testdox addHost() normalizes the host
	*/
	public function testAddHostNormalize()
	{
		$this->configurator->MediaEmbed->add('mastodon');

		$mastodonHelper = new MastodonHelper($this->configurator);
		$mastodonHelper->addHost('TEST.LOCAL');

		$parser   = $this->getParser();
		$actual   = $parser->parse('https://test.local/@user/123');
		$expected = '<r><MASTODON host="test.local" id="123" name="user">https://test.local/@user/123</MASTODON></r>';

		$this->assertXmlStringEqualsXmlString($expected, $actual);
	}

	/**
	* @testdox addHost() adds the Mastodon media site if it's not enabled yet
	*/
	public function testAddHostCreate()
	{
		$mastodonHelper = new MastodonHelper($this->configurator);
		$mastodonHelper->addHost('TEST.LOCAL');

		$parser   = $this->getParser();
		$actual   = $parser->parse('https://test.local/@user/123');
		$expected = '<r><MASTODON host="test.local" id="123" name="user">https://test.local/@user/123</MASTODON></r>';

		$this->assertXmlStringEqualsXmlString($expected, $actual);
	}
}