<?php

namespace s9e\TextFormatter\Tests\Configurator\RulesGenerators;

/**
* @covers s9e\TextFormatter\Configurator\RulesGenerators\BlockElementsFosterFormattingElements
*/
class BlockElementsFosterFormattingElementsTest extends AbstractTestClass
{
	/**
	* @testdox <div> has a fosterParent rule for <b>
	*/
	public function testDivFosterB()
	{
		$this->assertTargetedRules(
			'<div><xsl:apply-templates/></div>',
			'<b><xsl:apply-templates/></b>',
			['fosterParent']
		);
	}

	/**
	* @testdox <div> does not have a fosterParent rule for <div>
	*/
	public function testDivNoFosterDiv()
	{
		$this->assertTargetedRules(
			'<div><xsl:apply-templates/></div>',
			'<div><xsl:apply-templates/></div>',
			[]
		);
	}

	/**
	* @testdox <b> does not have a fosterParent rule for <b>
	*/
	public function testBNoFosterB()
	{
		$this->assertTargetedRules(
			'<b><xsl:apply-templates/></b>',
			'<b><xsl:apply-templates/></b>',
			[]
		);
	}

	/**
	* @testdox <div><br></div> does not have a fosterParent rule for <b>
	*/
	public function testBlockVoidNoFoster()
	{
		$this->assertTargetedRules(
			'<div><br/></div>',
			'<b><xsl:apply-templates/></b>',
			[]
		);
	}

	/**
	* @testdox <div><iframe></div> does not have a fosterParent rule for <b>
	*/
	public function testBlockEmbedNoFoster()
	{
		$this->assertTargetedRules(
			'<div><iframe/></div>',
			'<b><xsl:apply-templates/></b>',
			[]
		);
	}
}