<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\Helpers\HTML5\RulesGenerator;
use s9e\TextFormatter\Configurator\Items\UnsafeTemplate;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\HTML5\RulesGenerator
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

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'DIV' => [
					'allowChild' => ['DIV'  => 'DIV', 'SPAN' => 'SPAN']
				]
			],
			$rules['tags']
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

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'SPAN' => [
					'denyChild' => ['DIV' => 'DIV']
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox <a> has a denyDescendant rule for <a>
	*/
	public function testDenyDescendant()
	{
		$tags = new TagCollection;
		$tags->add('A')->defaultTemplate = '<a><xsl:apply-templates/></a>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'A' => [
					'denyDescendant' => ['A' => 'A']
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox <a> has a denyChild rule for <a>
	*/
	public function testDenyChildRemovedDueToDenyDescendant()
	{
		$tags = new TagCollection;
		$tags->add('A')->defaultTemplate = '<a><xsl:apply-templates/></a>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'A' => [
					'denyChild' => ['A' => 'A']
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Generates an autoClose rule for <hr/>
	*/
	public function testAutoClose()
	{
		$tags = new TagCollection;
		$tags->add('HR')->defaultTemplate = '<hr/>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'HR' => [
					'autoClose' => true
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Does not generate an autoClose rule for <span>
	*/
	public function testNoAutoClose()
	{
		$tags = new TagCollection;
		$tags->add('SPAN')->defaultTemplate = '<span><xsl:apply-templates/></span>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'SPAN' => [
					'autoClose' => null
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Generates an autoReopen rule for <b>
	*/
	public function testAutoReopen()
	{
		$tags = new TagCollection;
		$tags->add('B')->defaultTemplate = '<b><xsl:apply-templates/></b>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'B' => [
					'autoReopen' => true
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Does not generate an autoReopen rule for <div>
	*/
	public function testNoAutoReopen()
	{
		$tags = new TagCollection;
		$tags->add('DIV')->defaultTemplate = '<div><xsl:apply-templates/></div>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'DIV' => [
					'autoReopen' => null
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Generates a denyAll rule for <hr>
	*/
	public function testDenyAllHr()
	{
		$tags = new TagCollection;
		$tags->add('HR')->defaultTemplate = '<hr><xsl:apply-templates/></hr>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'HR' => [
					'denyAll' => true
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Generates a denyAll rule for <style>
	*/
	public function testDenyAllStyle()
	{
		$tags = new TagCollection;
		$tags->add('STYLE')->templates->set(
			'',
			new UnsafeTemplate('<style><xsl:apply-templates/></style>')
		);

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'STYLE' => [
					'denyAll' => true
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Generates an ignoreText rule for <ul>
	*/
	public function testIgnoreText()
	{
		$tags = new TagCollection;
		$tags->add('UL')->defaultTemplate = '<ul><xsl:apply-templates/></ul>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'UL' => [
					'ignoreText' => true
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Does not generate an ignoreText rule for <b>
	*/
	public function testNotIgnoreText()
	{
		$tags = new TagCollection;
		$tags->add('B')->defaultTemplate = '<b><xsl:apply-templates/></b>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'B' => [
					'ignoreText' => null
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Generates an isTransparent rule for <a>
	*/
	public function testIsTransparent()
	{
		$tags = new TagCollection;
		$tags->add('A')->defaultTemplate = '<a><xsl:apply-templates/></a>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'A' => [
					'isTransparent' => true
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Does not generate an isTransparent rule for <b>
	*/
	public function testNotIsTransparent()
	{
		$tags = new TagCollection;
		$tags->add('B')->defaultTemplate = '<b><xsl:apply-templates/></b>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'B' => [
					'isTransparent' => null
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Does not generate a noBrChild rule for <a>
	*/
	public function testNotNoBrChildA()
	{
		$tags = new TagCollection;
		$tags->add('A')->defaultTemplate = '<a><xsl:apply-templates/></a>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'A' => [
					'noBrChild' => null
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Does not generate a noBrChild rule for a template composed entirely of <xsl:apply-templates/>
	*/
	public function testNotNoBrChildTransparent()
	{
		$tags = new TagCollection;
		$tags->add('A')->defaultTemplate = '<xsl:apply-templates/>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'A' => [
					'noBrChild' => null
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Does not generate a noBrDescendant rule for <ol>
	*/
	public function testNotNoBrDescendantOl()
	{
		$tags = new TagCollection;
		$tags->add('OL')->defaultTemplate = '<ol><xsl:apply-templates/></ol>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'OL' => [
					'noBrDescendant' => null
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Generates a noBrDescendant rule for <pre>
	*/
	public function testNoBrDescendantPre()
	{
		$tags = new TagCollection;
		$tags->add('PRE')->defaultTemplate = '<pre><xsl:apply-templates/></pre>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'PRE' => [
					'noBrDescendant' => true
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Generates a noBrDescendant rule for <style>
	*/
	public function testNoBrChildStyle()
	{
		$tags = new TagCollection;
		$tags->add('STYLE')->templates->set(
			'',
			new UnsafeTemplate('<style><xsl:apply-templates/></style>')
		);

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'STYLE' => [
					'noBrChild' => true
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Root has an allowChild rule for <span>
	*/
	public function testRootAllowChild()
	{
		$tags = new TagCollection;
		$tags->add('SPAN')->defaultTemplate = '<span><xsl:apply-templates/></span>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'allowChild' => ['SPAN' => 'SPAN']
			],
			$rules['root']
		);
	}

	/**
	* @testdox Root has a denyChild rule for <li> if parentHTML is not specified
	*/
	public function testRootDenyChild()
	{
		$tags = new TagCollection;
		$tags->add('LI')->defaultTemplate = '<li><xsl:apply-templates/></li>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'denyChild' => ['LI' => 'LI']
			],
			$rules['root']
		);
	}

	/**
	* @testdox Root has an allowChild rule for <li> if parentHTML is <ul>
	*/
	public function testParentHTML()
	{
		$tags = new TagCollection;
		$tags->add('LI')->defaultTemplate = '<li><xsl:apply-templates/></li>';

		$rules = RulesGenerator::getRules($tags, ['parentHTML' => '<ul>']);

		$this->assertArrayMatches(
			[
				'allowChild' => ['LI' => 'LI']
			],
			$rules['root']
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

		$rules = RulesGenerator::getRules($tags, ['parentHTML' => '<a>']);

		$this->assertArrayMatches(
			[
				'SPAN' => [
					'allowChild' => ['SPAN' => 'SPAN']
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Generates a ignoreSurroundingWhitespace rule for <div>
	*/
	public function testIgnoreSurroundingWhitespace()
	{
		$tags = new TagCollection;
		$tags->add('DIV')->defaultTemplate = '<div><xsl:apply-templates/></div>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'DIV' => [
					'ignoreSurroundingWhitespace' => true
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Does not generate a ignoreSurroundingWhitespace rule for <span>
	*/
	public function testNoIgnoreSurroundingWhitespace()
	{
		$tags = new TagCollection;
		$tags->add('SPAN')->defaultTemplate = '<span><xsl:apply-templates/></span>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'SPAN' => [
					'ignoreSurroundingWhitespace' => null
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Generates a breakParagraph rule for <blockquote>
	*/
	public function testBreakParagraph()
	{
		$tags = new TagCollection;
		$tags->add('QUOTE')->defaultTemplate = '<blockquote><xsl:apply-templates/></blockquote>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'QUOTE' => [
					'breakParagraph' => true
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Does not generate a breakParagraph rule for <b>
	*/
	public function testNoBreakParagraph()
	{
		$tags = new TagCollection;
		$tags->add('B')->defaultTemplate = '<b><xsl:apply-templates/></b>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'B' => [
					'breakParagraph' => null
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Generates a createParagraphs rule for <blockquote> if rootRules has a createParagraphs rule
	*/
	public function testCreateParagraphs()
	{
		$tags = new TagCollection;
		$tags->add('QUOTE')->defaultTemplate = '<blockquote><xsl:apply-templates/></blockquote>';

		$rootRules = new Ruleset;
		$rootRules->createParagraphs();

		$rules = RulesGenerator::getRules($tags, ['rootRules' => $rootRules]);

		$this->assertArrayMatches(
			[
				'QUOTE' => [
					'createParagraphs' => true
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Does not generate a createParagraphs rule for <blockquote> if rootRules does not have a createParagraphs rule
	*/
	public function testNoCreateParagraphsNoRoot()
	{
		$tags = new TagCollection;
		$tags->add('QUOTE')->defaultTemplate = '<blockquote><xsl:apply-templates/></blockquote>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			[
				'QUOTE' => [
					'createParagraphs' => null
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Does not generate a createParagraphs rule for <span>
	*/
	public function testNoCreateParagraphsSpan()
	{
		$tags = new TagCollection;
		$tags->add('SPAN')->defaultTemplate = '<span><xsl:apply-templates/></span>';

		$rootRules = new Ruleset;
		$rootRules->createParagraphs();

		$rules = RulesGenerator::getRules($tags, ['rootRules' => $rootRules]);

		$this->assertArrayMatches(
			[
				'SPAN' => [
					'createParagraphs' => null
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Does not generate a createParagraphs rule for <p>
	*/
	public function testNoCreateParagraphsP()
	{
		$tags = new TagCollection;
		$tags->add('P')->defaultTemplate = '<p><xsl:apply-templates/></p>';

		$rootRules = new Ruleset;
		$rootRules->createParagraphs();

		$rules = RulesGenerator::getRules($tags, ['rootRules' => $rootRules]);

		$this->assertArrayMatches(
			[
				'P' => [
					'createParagraphs' => null
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Uses the renderer to generate a template
	*/
	public function testRenderer()
	{
		$tags = $this->configurator->tags;
		$tags->add('DIV')->defaultTemplate = new UnsafeTemplate(
			'<xsl:element name="{\'div\'}"><xsl:apply-templates/></xsl:element>'
		);
		$tags->add('A')->defaultTemplate = new UnsafeTemplate(
			'<xsl:element name="{\'a\'}"><xsl:apply-templates/></xsl:element>'
		);
		$rules = RulesGenerator::getRules(
			$tags,
			['renderer' => $this->configurator->getRenderer()]
		);

		$this->assertArrayMatches(
			[
				'DIV' => [
					'allowChild' => ['DIV' => 'DIV', 'A' => 'A']
				],
				'A' => [
					'denyChild'  => ['A' => 'A']
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Uses the renderer to generate a template
	*/
	public function testRendererPrefixedTag()
	{
		$tags = $this->configurator->tags;
		$tags->add('html:a')->defaultTemplate = new UnsafeTemplate(
			'<xsl:element name="{local-name()}"><xsl:apply-templates/></xsl:element>'
		);

		$rules = RulesGenerator::getRules(
			$tags,
			['renderer' => $this->configurator->getRenderer()]
		);

		$this->assertArrayMatches(
			[
				'html:a' => [
					'denyChild'  => ['html:a' => 'html:a']
				]
			],
			$rules['tags']
		);
	}

	/**
	* @testdox Uses the renderer to generate a template
	*/
	public function testRendererAttributes()
	{
		$tags = $this->configurator->tags;
		$tags->add('A')->defaultTemplate   = '<a><xsl:apply-templates/></a>';
		$tags->add('IMG')->defaultTemplate = '<img/>';
		$tags->add('html:img')->attributes->add('usemap');

		$this->configurator->stylesheet->setWildcardTemplate(
			'html',
			new UnsafeTemplate(
				'<xsl:element name="{local-name()}"><xsl:copy-of select="@*"/></xsl:element>'
			)
		);

		$rules = RulesGenerator::getRules(
			$tags,
			['renderer' => $this->configurator->getRenderer()]
		);

		$this->assertArrayMatches(
			[
				'A' => [
					'allowChild' => ['IMG' => 'IMG'],
					'denyChild'  => ['A' => 'A', 'html:img' => 'html:img']
				]
			],
			$rules['tags']
		);
	}
}