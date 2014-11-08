<?php

namespace s9e\TextFormatter\Tests\Configurator\RulesGenerators;

/**
* @covers s9e\TextFormatter\Configurator\RulesGenerators\EnforceContentModels
*/
class EnforceContentModelsTest extends AbstractTest
{
	/**
	* @testdox <b> has a denyChild rule for <div>
	*/
	public function testDenyChild()
	{
		$this->assertTargetedRules(
			'<b><xsl:apply-templates/></b>',
			'<div><xsl:apply-templates/></div>',
			array('denyChild')
		);
	}

	/**
	* @testdox <a> has a denyChild and a denyDescendant rule for <a>
	*/
	public function testDenyDescendant()
	{
		$this->assertTargetedRules(
			'<a><xsl:apply-templates/></a>',
			'<a><xsl:apply-templates/></a>',
			array('denyChild', 'denyDescendant')
		);
	}

	/**
	* @testdox <p> does not have a rule for <p>
	*/
	public function testPNoRuleB()
	{
		$this->assertTargetedRules(
			'<p><xsl:apply-templates/></p>',
			'<b><xsl:apply-templates/></b>',
			array()
		);
	}

	/**
	* @testdox Generates an isTransparent rule for <a>
	*/
	public function testIsTransparent()
	{
		$this->assertBooleanRules(
			'<a><xsl:apply-templates/></a>',
			array('isTransparent' => true)
		);
	}

	/**
	* @testdox Generates an isTransparent rule for a template composed entirely of <xsl:apply-templates/>
	*/
	public function testFullTransparent()
	{
		$this->assertBooleanRules(
			'<xsl:apply-templates/>',
			array('isTransparent' => true)
		);
	}

	/**
	* @testdox Does not generate any boolean rules for <b>
	*/
	public function testNoBoolean()
	{
		$this->assertBooleanRules(
			'<b><xsl:apply-templates/></b>',
			array()
		);
	}

	/**
	* @testdox Generates a disableAutoLineBreaks rule, a preventLineBreaks rule and a suspendAutoLineBreaks rule for <style>
	*/
	public function testLineBreaksStyle()
	{
		$this->assertBooleanRules(
			'<style><xsl:apply-templates/></style>',
			array(
				'disableAutoLineBreaks' => true,
				'preventLineBreaks'     => true,
				'suspendAutoLineBreaks' => true
			)
		);
	}

	/**
	* @testdox Generates a preventLineBreaks rule and a suspendAutoLineBreaks rule for <ul>
	*/
	public function testLineBreaksUl()
	{
		$this->assertBooleanRules(
			'<ul><xsl:apply-templates/></ul>',
			array('preventLineBreaks' => true, 'suspendAutoLineBreaks' => true)
		);
	}
}