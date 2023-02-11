<?php

namespace s9e\TextFormatter\Tests\Configurator\RulesGenerators;

/**
* @covers s9e\TextFormatter\Configurator\RulesGenerators\IgnoreTextIfDisallowed
*/
class IgnoreTextIfDisallowedTest extends AbstractTestClass
{
	/**
	* @testdox Generates an ignoreText rule for <ul>
	*/
	public function testIgnoreText()
	{
		$this->assertBooleanRules(
			'<ul><xsl:apply-templates/></ul>',
			['ignoreText' => true]
		);
	}

	/**
	* @testdox Does not generate an ignoreText rule for <b>
	*/
	public function testNotIgnoreText()
	{
		$this->assertBooleanRules(
			'<b><xsl:apply-templates/></b>',
			[]
		);
	}
}