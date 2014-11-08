<?php

namespace s9e\TextFormatter\Tests\Configurator\RulesGenerators;

/**
* @covers s9e\TextFormatter\Configurator\RulesGenerators\ManageParagraphs
*/
class ManageParagraphsTest extends AbstractTest
{
	/**
	* @testdox Generates a breakParagraph rule for <ol>
	*/
	public function testBreakParagraph()
	{
		$this->assertBooleanRules(
			'<ol><xsl:apply-templates/></ol>',
			array('breakParagraph' => true)
		);
	}

	/**
	* @testdox Does not generate any rules for <b>
	*/
	public function testNoBreakParagraph()
	{
		$this->assertBooleanRules(
			'<b><xsl:apply-templates/></b>',
			array()
		);
	}

	/**
	* @testdox Generates a breakParagraph and a createParagraphs rule for <blockquote>
	*/
	public function testCreateParagraphs()
	{
		$this->assertBooleanRules(
			'<blockquote><xsl:apply-templates/></blockquote>',
			array('breakParagraph' => true, 'createParagraphs' => true)
		);
	}

	/**
	* @testdox Generates a breakParagraph rule but no createParagraphs rule for <p>
	*/
	public function testNoCreateParagraphsP()
	{
		$this->assertBooleanRules(
			'<p><xsl:apply-templates/></p>',
			array('breakParagraph' => true)
		);
	}
}