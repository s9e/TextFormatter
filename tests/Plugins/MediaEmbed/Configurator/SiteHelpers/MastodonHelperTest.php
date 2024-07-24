<?php declare(strict_types=1);

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed\Configurator\SiteHelpers;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\SiteHelpers\MastodonHelper;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\SiteHelpers\AbstractConfigurableHostHelper
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\SiteHelpers\AbstractSiteHelper
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\SiteHelpers\MastodonHelper
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

	/**
	* @testdox setHosts() resets previously allowed hosts
	*/
	public function testSetHost()
	{
		$mastodonHelper = new MastodonHelper($this->configurator);
		$mastodonHelper->setHosts(['test.local']);

		$parser   = $this->getParser();
		$actual   = $parser->parse('https://mastodon.social/@HackerNewsBot/100181134752056592 https://test.local/@user/123');
		$expected = '<r>https://mastodon.social/@HackerNewsBot/100181134752056592 <MASTODON host="test.local" id="123" name="user">https://test.local/@user/123</MASTODON></r>';

		$this->assertXmlStringEqualsXmlString($expected, $actual);
	}
}