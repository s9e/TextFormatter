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
	public function testImplementsArrayAccess()
	{
		$this->assertInstanceOf('ArrayAccess', new RulesGenerator);
	}

	/**
	* @testdox Implements Iterator
	*/
	public function testImplementsIterator()
	{
		$this->assertInstanceOf('Iterator', new RulesGenerator);
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

	public static function getDefault()
	{
		return [
			[
				[
					'B' => '<b><xsl:apply-templates/></b>'
				],
				[
					'root' => [
						'allowChild'      => ['B'],
						'allowDescendant' => ['B']
					],
					'tags' => [
						'B' => [
							'allowChild'      => ['B'],
							'allowDescendant' => ['B'],
							'autoReopen'      => true
						]
					]
				]
			],
			[
				[
					'LI' => '<li><xsl:apply-templates/></li>',
					'OL' => '<ol><xsl:apply-templates/></ol>'
				],
				[
					'root' => [
						'allowChild'      => ['OL'],
						'allowDescendant' => ['LI', 'OL']
					],
					'tags' => [
						'LI' => [
							'allowChild'                  => ['OL'],
							'allowDescendant'             => ['LI', 'OL'],
							'closeParent'                 => ['LI'],
							'ignoreSurroundingWhitespace' => true
						],
						'OL' => [
							'allowChild'                  => ['LI'],
							'allowDescendant'             => ['LI', 'OL'],
							'breakParagraph'              => true,
							'ignoreSurroundingWhitespace' => true,
							'ignoreText'                  => true,
							'preventLineBreaks'           => true,
							'suspendAutoLineBreaks'       => true
						]
					]
				]
			],
			[
				[
					'X' => null
				],
				[
					'root' => [
						'allowChild'      => ['X'],
						'allowDescendant' => ['X']
					],
					'tags' => [
						'X' => [
							'allowChild'      => ['X'],
							'allowDescendant' => ['X'],
							'isTransparent'   => true
						]
					]
				]
			],
			[
				[
					'CODE' => '<pre><code><xsl:apply-templates/></code></pre>'
				],
				[
					'root' => [
						'allowChild'      => ['CODE'],
						'allowDescendant' => ['CODE'],
					],
					'tags' => [
						'CODE' => [
							'allowDescendant'             => ['CODE'],
							'breakParagraph'              => true,
							'disableAutoLineBreaks'       => true,
							'ignoreTags'                  => true,
							'ignoreSurroundingWhitespace' => true,
							'trimFirstLine'               => true
						]
					]
				]
			],
			[
				[
					'B' => '<b><xsl:apply-templates/></b>',
					'X' => '<div>...</div>'
				],
				[
					'root' => [
						'allowChild'      => ['B', 'X'],
						'allowDescendant' => ['B', 'X']
					],
					'tags' => [
						'B' => [
							'allowChild'      => ['B'],
							'allowDescendant' => ['B', 'X'],
							'autoReopen'      => true
						],
						'X' => [
							'autoClose'                   => true,
							'breakParagraph'              => true,
							'closeParent'                 => ['B'],
							'disableAutoLineBreaks'       => true,
							'ignoreSurroundingWhitespace' => true,
							'preventLineBreaks'           => true,
							'suspendAutoLineBreaks'       => true
						]
					]
				]
			],
		];
	}
}