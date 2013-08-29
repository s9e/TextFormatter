<?php

namespace s9e\TextFormatter\Tests\Configurator\RulesGenerators;

/**
* @covers s9e\TextFormatter\Configurator\RulesGenerators\NoBrIfWhitespaceIsPreserved
*/
class NoBrIfWhitespaceIsPreservedTest extends AbstractTest
{
	/**
	* @testdox Does not generate a noBrDescendant rule for <ol>
	*/
	public function testNotNoBrDescendantOl()
	{
		$this->assertBooleanRules(
			'<ol><xsl:apply-templates/></ol>',
			[]
		);
	}

	/**
	* @testdox Generates a noBrDescendant rule for <pre>
	*/
	public function testNoBrDescendantPre()
	{
		$this->assertBooleanRules(
			'<pre><xsl:apply-templates/></pre>',
			['noBrDescendant' => true]
		);
	}
}