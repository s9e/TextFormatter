<?php declare(strict_types=1);

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed\Configurator\SiteHelpers;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\SiteHelpers\BlueskyHelper;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\SiteHelpers\AbstractConfigurableHostHelper
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\SiteHelpers\AbstractSiteHelper
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\SiteHelpers\BlueskyHelper
*/
class BlueskyHelperTest extends Test
{
	/**
	* @testdox addHost() normalizes the host
	*/
	public function testAddHostNormalize()
	{
		$this->configurator->MediaEmbed->add('bluesky');

		$blueskyHelper = new BlueskyHelper($this->configurator);
		$blueskyHelper->addHost('BLUESKY.LOCAL');

		$parser   = $this->getParser();
		$actual   = $parser->parse('https://embed.bluesky.local/oembed?format=json&url=at%3A%2F%2Fdid%3Aplc%3Az72i7hdynmk6r22z27h6tvur%2Fapp.bsky.feed.post%2F3kkrqzuydho2v');
		$expected = '<r><BLUESKY embedder="embed.bluesky.local" url="at://did:plc:z72i7hdynmk6r22z27h6tvur/app.bsky.feed.post/3kkrqzuydho2v">https://embed.bluesky.local/oembed?format=json&amp;url=at%3A%2F%2Fdid%3Aplc%3Az72i7hdynmk6r22z27h6tvur%2Fapp.bsky.feed.post%2F3kkrqzuydho2v</BLUESKY></r>';

		$this->assertXmlStringEqualsXmlString($expected, $actual);
	}

	/**
	* @testdox addHost() adds the Bluesky media site if it's not enabled yet
	*/
	public function testAddHostCreate()
	{
		$blueskyHelper = new BlueskyHelper($this->configurator);
		$blueskyHelper->addHost('BLUESKY.LOCAL');

		$parser   = $this->getParser();
		$actual   = $parser->parse('https://embed.bluesky.local/oembed?format=json&url=at%3A%2F%2Fdid%3Aplc%3Az72i7hdynmk6r22z27h6tvur%2Fapp.bsky.feed.post%2F3kkrqzuydho2v');
		$expected = '<r><BLUESKY embedder="embed.bluesky.local" url="at://did:plc:z72i7hdynmk6r22z27h6tvur/app.bsky.feed.post/3kkrqzuydho2v">https://embed.bluesky.local/oembed?format=json&amp;url=at%3A%2F%2Fdid%3Aplc%3Az72i7hdynmk6r22z27h6tvur%2Fapp.bsky.feed.post%2F3kkrqzuydho2v</BLUESKY></r>';

		$this->assertXmlStringEqualsXmlString($expected, $actual);
	}

	/**
	* @testdox addHost() updates the embedded regexp
	*/
	public function testAddHostRegexp()
	{
		$blueskyHelper = new BlueskyHelper($this->configurator);
		$blueskyHelper->addHost('BLUESKY.LOCAL');

		$parser   = $this->getParser();
		$actual   = $parser->parse('https://embed.bluesky.local/oembed?format=json&url=at%3A%2F%2Fdid%3Aplc%3Az72i7hdynmk6r22z27h6tvur%2Fapp.bsky.feed.post%2F3kkrqzuydho2v');
		$expected = '<r><BLUESKY embedder="embed.bluesky.local" url="at://did:plc:z72i7hdynmk6r22z27h6tvur/app.bsky.feed.post/3kkrqzuydho2v">https://embed.bluesky.local/oembed?format=json&amp;url=at%3A%2F%2Fdid%3Aplc%3Az72i7hdynmk6r22z27h6tvur%2Fapp.bsky.feed.post%2F3kkrqzuydho2v</BLUESKY></r>';

		$actual   = $parser->parse('https://embed.bluesky.local.evil/oembed?format=json&url=at%3A%2F%2Fdid%3Aplc%3Az72i7hdynmk6r22z27h6tvur%2Fapp.bsky.feed.post%2F3kkrqzuydho2v');
		$expected = '<t>https://embed.bluesky.local.evil/oembed?format=json&amp;url=at%3A%2F%2Fdid%3Aplc%3Az72i7hdynmk6r22z27h6tvur%2Fapp.bsky.feed.post%2F3kkrqzuydho2v</t>';

		$this->assertXmlStringEqualsXmlString($expected, $actual);
	}
}