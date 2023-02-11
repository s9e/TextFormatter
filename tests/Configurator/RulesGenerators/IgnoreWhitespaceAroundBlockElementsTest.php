<?php

namespace s9e\TextFormatter\Tests\Configurator\RulesGenerators;

/**
* @covers s9e\TextFormatter\Configurator\RulesGenerators\IgnoreWhitespaceAroundBlockElements
*/
class IgnoreWhitespaceAroundBlockElementsTest extends AbstractTestClass
{
	/**
	* @testdox Generates a ignoreSurroundingWhitespace rule for <div>
	*/
	public function testIgnoreSurroundingWhitespace()
	{
		$this->assertBooleanRules(
			'<div><xsl:apply-templates/></div>',
			['ignoreSurroundingWhitespace' => true]
		);
	}

	/**
	* @testdox Does not generate a ignoreSurroundingWhitespace rule for <span>
	*/
	public function testNoIgnoreSurroundingWhitespace()
	{
		$this->assertBooleanRules(
			'<span><xsl:apply-templates/></span>',
			[]
		);
	}
}