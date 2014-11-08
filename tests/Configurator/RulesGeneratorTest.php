<?php

namespace s9e\TextFormatter\Tests\Configurator;

use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\RulesGenerator;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\RulesGenerator
*/
class RulesGeneratorTest extends Test
{
	/**
	* @testdox Implements ArrayAccess
	*/
	public function textImplementsArrayAccess()
	{
		$this->assertInstanceOf('ArrayAccess', new RulesGenerator);
	}

	/**
	* @testdox Implements Iterator
	*/
	public function textImplementsIterator()
	{
		$this->assertInstanceOf('Iterator', new RulesGenerator);
	}

	/**
	* @testdox Root has a denyChild rule for <li> if parentHTML is not specified
	*/
	public function testRootDenyChild()
	{
		$rulesGenerator = new RulesGenerator;
		$tagCollection  = new TagCollection;
		$tagCollection->add('LI')->template = '<li><xsl:apply-templates/></li>';

		$rules = $rulesGenerator->getRules($tagCollection);

		$this->assertEquals(
			array(
				'denyChild' => array('LI')
			),
			$rules['root']
		);
	}

	/**
	* @testdox Root does not have a denyChild rule for <li> if parentHTML is <ul>
	*/
	public function testParentHTML()
	{
		$rulesGenerator = new RulesGenerator;
		$tagCollection  = new TagCollection;
		$tagCollection->add('LI')->template = '<li><xsl:apply-templates/></li>';

		$rules = $rulesGenerator->getRules($tagCollection, array('parentHTML' => '<ul>'));

		$this->assertArrayNotHasKey('denyChild', $rules['root']);
	}

	/**
	* @testdox Default rules
	* @dataProvider getDefault
	*/
	public function testDefault($tags, $expected)
	{
		$rulesGenerator = new RulesGenerator;
		$tagCollection  = new TagCollection;

		foreach ($tags as $tagName => $template)
		{
			$tag = $tagCollection->add($tagName);

			if (isset($template))
			{
				$tag->template = $template;
			}
		}

		$this->assertEquals($expected, $rulesGenerator->getRules($tagCollection));
	}

	public function getDefault()
	{
		return array(
			array(
				array(
					'B' => '<b><xsl:apply-templates/></b>'
				),
				array(
					'root' => array(),
					'tags' => array(
						'B' => array('autoReopen' => true)
					)
				)
			),
			array(
				array(
					'LI' => '<li><xsl:apply-templates/></li>',
					'OL' => '<ol><xsl:apply-templates/></ol>'
				),
				array(
					'root' => array(
						'denyChild' => array('LI')
					),
					'tags' => array(
						'LI' => array(
							'closeParent' => array('LI'),
							'denyChild'   => array('LI'),
							'ignoreSurroundingWhitespace' => true
						),
						'OL' => array(
							'denyChild'                   => array('OL'),
							'ignoreSurroundingWhitespace' => true,
							'ignoreText'                  => true,
							'preventLineBreaks'           => true,
							'suspendAutoLineBreaks'       => true
						)
					)
				)
			),
			array(
				array(
					'X' => null
				),
				array(
					'root' => array(),
					'tags' => array(
						'X' => array('isTransparent' => true)
					)
				)
			),
		);
	}
}