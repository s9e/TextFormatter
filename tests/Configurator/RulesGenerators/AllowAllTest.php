<?php

namespace s9e\TextFormatter\Tests\Configurator\RulesGenerators;

/**
* @covers s9e\TextFormatter\Configurator\RulesGenerators\AllowAll
*/
class AllowAllTest extends AbstractTest
{
	/**
	* @testdox <b> has a allowChild rule and a allowDescendant rule for <div>
	*/
	public function testAllowAll()
	{
		$this->assertTargetedRules(
			'<b><xsl:apply-templates/></b>',
			'<div><xsl:apply-templates/></div>',
			['allowChild', 'allowDescendant']
		);
	}
}