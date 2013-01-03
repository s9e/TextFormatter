<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use s9e\TextFormatter\Tests\Test;
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
			array(
				'DIV' => array(
					'allowChild' => array('DIV', 'SPAN')
				)
			),
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
			array(
				'SPAN' => array(
					'denyChild' => array('DIV')
				)
			),
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
			array(
				'A' => array(
					'denyDescendant' => array('A')
				)
			),
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
			array(
				'A' => array(
					'denyChild' => array('A')
				)
			),
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
			array(
				'HR' => array(
					'autoClose' => true
				)
			),
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
			array(
				'SPAN' => array(
					'autoClose' => null
				)
			),
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
			array(
				'B' => array(
					'autoReopen' => true
				)
			),
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
			array(
				'DIV' => array(
					'autoReopen' => null
				)
			),
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
			array(
				'HR' => array(
					'denyAll' => true
				)
			),
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
			array(
				'STYLE' => array(
					'denyAll' => true
				)
			),
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
			array(
				'UL' => array(
					'ignoreText' => true
				)
			),
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
			array(
				'B' => array(
					'ignoreText' => null
				)
			),
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
			array(
				'A' => array(
					'isTransparent' => true
				)
			),
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
			array(
				'B' => array(
					'isTransparent' => null
				)
			),
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
			array(
				'A' => array(
					'noBrChild' => null
				)
			),
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
			array(
				'OL' => array(
					'noBrDescendant' => null
				)
			),
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
			array(
				'PRE' => array(
					'noBrDescendant' => true
				)
			),
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
			array(
				'STYLE' => array(
					'noBrChild' => true
				)
			),
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
			array(
				'allowChild' => array('SPAN')
			),
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
			array(
				'denyChild' => array('LI')
			),
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

		$rules = RulesGenerator::getRules($tags, array('parentHTML' => '<ul>'));

		$this->assertArrayMatches(
			array(
				'allowChild' => array('LI')
			),
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

		$rules = RulesGenerator::getRules($tags, array('parentHTML' => '<a>'));

		$this->assertArrayMatches(
			array(
				'SPAN' => array(
					'allowChild' => array('SPAN')
				)
			),
			$rules['tags']
		);
	}

	/**
	* @testdox Generates a trimWhitespace rule for <div>
	*/
	public function testTrimWhitespace()
	{
		$tags = new TagCollection;
		$tags->add('DIV')->defaultTemplate = '<div><xsl:apply-templates/></div>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			array(
				'DIV' => array(
					'trimWhitespace' => true
				)
			),
			$rules['tags']
		);
	}

	/**
	* @testdox Does not generate a trimWhitespace rule for <span>
	*/
	public function testNoTrimWhitespace()
	{
		$tags = new TagCollection;
		$tags->add('SPAN')->defaultTemplate = '<span><xsl:apply-templates/></span>';

		$rules = RulesGenerator::getRules($tags);

		$this->assertArrayMatches(
			array(
				'SPAN' => array(
					'trimWhitespace' => null
				)
			),
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
			array('renderer' => $this->configurator->getRenderer())
		);

		$this->assertArrayMatches(
			array(
				'DIV' => array(
					'allowChild' => array('DIV', 'A')
				),
				'A' => array(
					'denyChild'  => array('A')
				)
			),
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
			array('renderer' => $this->configurator->getRenderer())
		);

		$this->assertArrayMatches(
			array(
				'html:a' => array(
					'denyChild'  => array('html:a')
				)
			),
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
			array('renderer' => $this->configurator->getRenderer())
		);

		$this->assertArrayMatches(
			array(
				'A' => array(
					'allowChild' => array('IMG'),
					'denyChild'  => array('A', 'html:img')
				)
			),
			$rules['tags']
		);
	}
}