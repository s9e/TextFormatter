<?php

namespace s9e\TextFormatter\Tests\Configurator\RulesGenerators;

/**
* @covers s9e\TextFormatter\Configurator\RulesGenerators\TrimFirstLineInCodeBlocks
*/
class TrimFirstLineInCodeBlocksTest extends AbstractTestClass
{
	/**
	* @testdox Generates a trimFirstLine rule for <pre><code><xsl:apply-templates/></code></pre>
	*/
	public function testPreCode()
	{
		$this->assertBooleanRules(
			'<pre><code><xsl:apply-templates/></code></pre>',
			['trimFirstLine' => true]
		);
	}

	/**
	* @testdox Does not generate any rules for <pre><xsl:apply-templates/></pre>
	*/
	public function testPreNoCode()
	{
		$this->assertBooleanRules(
			'<pre><xsl:apply-templates/></pre>',
			[]
		);
	}

	/**
	* @testdox Does not generate any rules for <pre><code></code></pre>
	*/
	public function testPreCodeNoApplyTemplates()
	{
		$this->assertBooleanRules(
			'<pre><code></code></pre>',
			[]
		);
	}
}