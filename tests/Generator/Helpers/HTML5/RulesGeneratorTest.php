<?php

namespace s9e\TextFormatter\Tests\Generator\Helpers;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Generator\Collections\TagCollection;
use s9e\TextFormatter\Generator\Helpers\HTML5\RulesGenerator;

/**
* @covers s9e\TextFormatter\Generator\Helpers\HTML5\RulesGenerator
*/
class RulesGeneratorTest extends Test
{
	/**
	* @testdox <div> has an allowChild rule for <div> and <span>
	*/
	public function testAllowChild()
	{
		$tags = new TagCollection;

		$tags->add('DIV')->defaultTemplate  = '<div><xsl:apply-templates/></div>';
		$tags->add('SPAN')->defaultTemplate = '<span><xsl:apply-templates/></span>';

		$this->assertArrayMatches(
			array(
				'DIV' => array(
					'allowChild' => array('DIV', 'SPAN')
				)
			),
			RulesGenerator::getRules($tags)
		);
	}

	/**
	* @testdox <span> has a denyChild rule for <div>
	*/
	public function testDenyChild()
	{
		$tags = new TagCollection;

		$tags->add('DIV')->defaultTemplate  = '<div><xsl:apply-templates/></div>';
		$tags->add('SPAN')->defaultTemplate = '<span><xsl:apply-templates/></span>';

		$this->assertArrayMatches(
			array(
				'SPAN' => array(
					'denyChild' => array('DIV')
				)
			),
			RulesGenerator::getRules($tags)
		);
	}

	/**
	* @testdox <a> has a denyDescendant rule for <a>
	*/
	public function testDenyDescendant()
	{
		$tags = new TagCollection;

		$tags->add('A')->defaultTemplate = '<a><xsl:apply-templates/></a>';

		$this->assertArrayMatches(
			array(
				'A' => array(
					'denyDescendant' => array('A')
				)
			),
			RulesGenerator::getRules($tags)
		);
	}

	/**
	* @testdox <a> has a denyChild rule for <a>
	*/
	public function testDenyChildRemovedDueToDenyDescendant()
	{
		$tags = new TagCollection;

		$tags->add('A')->defaultTemplate = '<a><xsl:apply-templates/></a>';

		$this->assertArrayMatches(
			array(
				'A' => array(
					'denyChild' => array('A')
				)
			),
			RulesGenerator::getRules($tags)
		);
	}

	/**
	* @testdox Generates an autoReopen rule for <b>
	*/
	public function testAutoReopen()
	{
		$tags = new TagCollection;

		$tags->add('B')->defaultTemplate = '<b><xsl:apply-templates/></b>';

		$this->assertArrayMatches(
			array(
				'B' => array(
					'autoReopen' => true
				)
			),
			RulesGenerator::getRules($tags)
		);
	}

	/**
	* @testdox Generates an isTransparent rule for <a>
	*/
	public function testIsTransparent()
	{
		$tags = new TagCollection;

		$tags->add('A')->defaultTemplate = '<a><xsl:apply-templates/></a>';

		$this->assertArrayMatches(
			array(
				'A' => array(
					'isTransparent' => true
				)
			),
			RulesGenerator::getRules($tags)
		);
	}

	/**
	* @testdox <li> has a disallowAtRoot rule if parentHTML is not specified
	*/
	public function testDisallowAtRoot()
	{
		$tags = new TagCollection;

		$tags->add('LI')->defaultTemplate = '<li><xsl:apply-templates/></li>';

		$this->assertArrayMatches(
			array(
				'LI' => array(
					'disallowAtRoot' => true
				)
			),
			RulesGenerator::getRules($tags)
		);
	}

	/**
	* @testdox <li> does not have a disallowAtRoot rule if parentHTML is <ul>
	* @depends testDisallowAtRoot
	*/
	public function testParentHTML()
	{
		$tags = new TagCollection;

		$tags->add('LI')->defaultTemplate = '<li><xsl:apply-templates/></li>';

		$this->assertArrayMatches(
			array(
				'LI' => array(
					'disallowAtRoot' => null
				)
			),
			RulesGenerator::getRules($tags, array('parentHTML' => '<ul>'))
		);
	}

	/**
	* @testdox <span> has a denyChild rule for <a> if parentHTML is <a>
	*/
	public function testParentHTMLDeniesDescendant()
	{
		$tags = new TagCollection;

		$tags->add('SPAN')->defaultTemplate = '<span><xsl:apply-templates/></span>';
		$tags->add('A')->defaultTemplate    = '<a><xsl:apply-templates/></a>';

		$this->assertArrayMatches(
			array(
				'SPAN' => array(
					'allowChild' => array('SPAN')
				)
			),
			RulesGenerator::getRules($tags, array('parentHTML' => '<a>'))
		);
	}
}