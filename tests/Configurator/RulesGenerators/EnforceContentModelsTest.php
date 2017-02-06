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
			['denyChild']
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
			['denyChild', 'denyDescendant']
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
			[]
		);
	}

	/**
	* @testdox <iframe> with no <xsl:apply-templates/> has a denyChild rule for <div>
	*/
	public function testIframeDeniesDiv()
	{
		$this->assertTargetedRules(
			'<iframe/>',
			'<div/>',
			['denyChild']
		);
	}

	/**
	* @testdox <iframe> with no <xsl:apply-templates/> has no rules for <span>
	*/
	public function testIframeAllowsSpan()
	{
		$this->assertTargetedRules(
			'<iframe/>',
			'<span/>',
			[]
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
	* @testdox <script> has a denyChild and a denyDescendant rule for <a>
	*/
	public function testScriptDeniesTags()
	{
		$this->assertTargetedRules(
			'<script><xsl:apply-templates/></script>',
			'<a/>',
			['denyChild', 'denyDescendant']
		);
	}
}