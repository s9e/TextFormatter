<?php

namespace s9e\TextFormatter\Tests\Configurator\RulesGenerators;

/**
* @covers s9e\TextFormatter\Configurator\RulesGenerators\IgnoreTagsInCode
*/
class IgnoreTagsInCodeTest extends AbstractTestClass
{
	/**
	* @testdox Generates an ignoreTags rule for <code><xsl:apply-templates/></code>
	*/
	public function testIgnoreTags()
	{
		$this->assertBooleanRules(
			'<code><xsl:apply-templates/></code>',
			['ignoreTags' => true]
		);
	}

	/**
	* @testdox Generates an ignoreTags rule for <pre><code><xsl:apply-templates/></code></pre>
	*/
	public function testIgnoreTagsPreCode()
	{
		$this->assertBooleanRules(
			'<pre><code><xsl:apply-templates/></code></pre>',
			['ignoreTags' => true]
		);
	}

	/**
	* @testdox Does not generate an ignoreTags rule if <code> does not have an xsl:apply-templates descendant
	*/
	public function testNotIgnoreTagsIfNoDescendant()
	{
		$this->assertBooleanRules(
			'<code>foo</code><xsl:apply-templates/>',
			[]
		);
	}

	/**
	* @testdox Does not generate an ignoreTags rule for <b>
	*/
	public function testNotIgnoreTags()
	{
		$this->assertBooleanRules(
			'<b><xsl:apply-templates/></b>',
			[]
		);
	}
}