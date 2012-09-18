<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Helpers;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\ConfigBuilder\Collections\PluginCollection;
use s9e\TextFormatter\ConfigBuilder\Collections\TagCollection;
use s9e\TextFormatter\ConfigBuilder\Helpers\StylesheetHelper;

/**
* @covers s9e\TextFormatter\ConfigBuilder\Helpers\StylesheetHelper
*/
class StylesheetHelperTest extends Test
{
	/**
	* @testdox Correctly declares namespaces for prefixed tags
	*/
	public function testPrefixedTag()
	{
		$tags = new TagCollection;

		$tags->add('bar:A');
		$tags->add('bar:B');
		$tags->add('foo:C');

		$xsl = StylesheetHelper::generate($tags);

		$this->assertContains('xmlns:bar="urn:s9e:TextFormatter:bar"', $xsl);
		$this->assertContains('xmlns:foo="urn:s9e:TextFormatter:foo"', $xsl);
	}
}