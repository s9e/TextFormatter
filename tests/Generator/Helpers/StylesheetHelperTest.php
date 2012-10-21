<?php

namespace s9e\TextFormatter\Tests\Generator\Helpers;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Generator\Collections\PluginCollection;
use s9e\TextFormatter\Generator\Collections\TagCollection;
use s9e\TextFormatter\Generator\Helpers\StylesheetHelper;

/**
* @covers s9e\TextFormatter\Generator\Helpers\StylesheetHelper
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

	/**
	* @testdox Predicates are preserved
	*/
	public function testPredicate()
	{
		$tags = new TagCollection;

		$tag = $tags->add('A');
		$tag->templates->add('@foo', '<hr/>');
		$tag->templates->add('@bar', '<br/>');

		$xsl = StylesheetHelper::generate($tags);

		$this->assertContains('<xsl:template match="A[@foo]"><hr/></xsl:template>', $xsl);
		$this->assertContains('<xsl:template match="A[@bar]"><br/></xsl:template>', $xsl);
	}

	/**
	* @testdox Identical templates are merged together
	*/
	public function testIdenticalTemplates()
	{
		$tags = new TagCollection;

		$tags->add('A')->defaultTemplate = '<b><xsl:apply-templates/></b>';
		$tags->add('B')->defaultTemplate = '<b><xsl:apply-templates/></b>';

		$this->assertContains(
			'<xsl:template match="A|B"><b><xsl:apply-templates/></b></xsl:template>',
			StylesheetHelper::generate($tags)
		);
	}

	/**
	* @testdox Empty templates are merged with the empty templates used for <st>, <et> and <i>
	*/
	public function testIdenticalEmptyTemplates()
	{
		$tags = new TagCollection;

		$tags->add('A')->defaultTemplate = '';
		$tags->add('B')->defaultTemplate = '';

		$this->assertContains(
			'<xsl:template match="A|B|st|et|i"/>',
			StylesheetHelper::generate($tags)
		);
	}
}