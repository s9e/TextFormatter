<?php

namespace s9e\TextFormatter\Tests\Configurator\RulesGenerators;

/**
* @covers s9e\TextFormatter\Configurator\RulesGenerators\EnforceContentModels
*/
class EnforceContentModelsTest extends AbstractTest
{
	/**
	* @testdox <div> has an allowChild rule and an allowDescendant rule for <b>
	*/
	public function testAllowBoth()
	{
		$this->assertTargetedRules(
			'<div><xsl:apply-templates/></div>',
			'<b><xsl:apply-templates/></b>',
			['allowChild', 'allowDescendant']
		);
	}

	/**
	* @testdox <a> has no rules for <a>
	*/
	public function testAllowNone()
	{
		$this->assertTargetedRules(
			'<a><xsl:apply-templates/></a>',
			'<a><xsl:apply-templates/></a>',
			[]
		);
	}

	/**
	* @testdox <ol> has an allowDescendant rule and no allowChild rule for <div>
	*/
	public function testAllowDescendantNotChild()
	{
		$this->assertTargetedRules(
			'<ol><xsl:apply-templates/></ol>',
			'<div><xsl:apply-templates/></div>',
			['allowDescendant']
		);
	}

	/**
	* @testdox Generates an isTransparent rule for <a>
	*/
	public function testIsTransparent()
	{
		$this->assertBooleanRules(
			'<a><xsl:apply-templates/></a>',
			['isTransparent' => true]
		);
	}

	/**
	* @testdox Generates an isTransparent rule for a template composed entirely of <xsl:apply-templates/>
	*/
	public function testFullTransparent()
	{
		$this->assertBooleanRules(
			'<xsl:apply-templates/>',
			['isTransparent' => true]
		);
	}

	/**
	* @testdox Does not generate any boolean rules for <b>
	*/
	public function testNoBoolean()
	{
		$this->assertBooleanRules(
			'<b><xsl:apply-templates/></b>',
			[]
		);
	}

	/**
	* @testdox Generates a disableAutoLineBreaks rule, a preventLineBreaks rule and a suspendAutoLineBreaks rule for <style>
	*/
	public function testLineBreaksStyle()
	{
		$this->assertBooleanRules(
			'<style><xsl:apply-templates/></style>',
			[
				'disableAutoLineBreaks' => true,
				'preventLineBreaks'     => true,
				'suspendAutoLineBreaks' => true
			]
		);
	}

	/**
	* @testdox Generates a preventLineBreaks rule and a suspendAutoLineBreaks rule for <ul>
	*/
	public function testLineBreaksUl()
	{
		$this->assertBooleanRules(
			'<ul><xsl:apply-templates/></ul>',
			['preventLineBreaks' => true, 'suspendAutoLineBreaks' => true]
		);
	}

	/**
	* @testdox <script> has no rules for <a>
	*/
	public function testScriptAllowsNoTags()
	{
		$this->assertTargetedRules(
			'<script><xsl:apply-templates/></script>',
			'<a/>',
			[]
		);
	}

	/**
	* @testdox A mixed inline/block template has no allowChild rule for <div>
	*/
	public function testMixedDeniesBlock()
	{
		$this->assertTargetedRules(
			'<div><xsl:apply-templates/></div><span><xsl:apply-templates/></span>',
			'<div/>',
			['allowDescendant']
		);
	}

	/**
	* @testdox A mixed inline/block template has an allowChild rule and an allowDescendant rule for <span>
	*/
	public function testMixedAllowsInline()
	{
		$this->assertTargetedRules(
			'<div><xsl:apply-templates/></div><span><xsl:apply-templates/></span>',
			'<span/>',
			['allowChild', 'allowDescendant']
		);
	}
}