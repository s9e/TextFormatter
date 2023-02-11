<?php

namespace s9e\TextFormatter\Tests\Configurator\RulesGenerators;

/**
* @covers s9e\TextFormatter\Configurator\RulesGenerators\AutoCloseIfVoid
*/
class AutoCloseIfVoidTest extends AbstractTestClass
{
	/**
	* @testdox Generates an autoClose rule for <hr/>
	*/
	public function testAutoClose()
	{
		$this->assertBooleanRules(
			'<hr/>',
			['autoClose' => true]
		);
	}

	/**
	* @testdox Does not generate an autoClose rule for <span>
	*/
	public function testNoAutoClose()
	{
		$this->assertBooleanRules(
			'<span><xsl:apply-templates/></span>',
			[]
		);
	}
}