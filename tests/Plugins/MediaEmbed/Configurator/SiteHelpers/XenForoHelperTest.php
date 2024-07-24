<?php declare(strict_types=1);

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed\Configurator\SiteHelpers;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\SiteHelpers\XenForoHelper;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\SiteHelpers\AbstractConfigurableHostHelper
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\SiteHelpers\AbstractSiteHelper
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\SiteHelpers\XenForoHelper
*/
class XenForoHelperTest extends Test
{

	/**
	* @testdox addHost() adds the XenForo media site if it's not enabled yet
	*/
	public function testAddHostCreate()
	{
		$xenforoHelper = new XenForoHelper($this->configurator);
		$xenforoHelper->addHost('xenforo.com');

		$parser   = $this->getParser();
		$actual   = $parser->parse('https://xenforo.com/community/threads/embed.217381/');
		$expected = '<r><XENFORO thread_id="217381" url="https://xenforo.com/community/">https://xenforo.com/community/threads/embed.217381/</XENFORO></r>';

		$this->assertXmlStringEqualsXmlString($expected, $actual);
	}
}