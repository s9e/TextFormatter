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
		return [
			[
				[
					'B' => '<b><xsl:apply-templates/></b>'
				],
				[
					'root' => [],
					'tags' => [
						'B' => ['autoReopen' => true]
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
						'denyChild' => ['LI']
					],
					'tags' => [
						'LI' => [
							'closeParent' => ['LI'],
							'denyChild'   => ['LI'],
							'ignoreSurroundingWhitespace' => true
						],
						'OL' => [
							'denyChild'                   => ['OL'],
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
					'root' => [],
					'tags' => [
						'X' => ['isTransparent' => true]
					]
				]
			],
			[
				[
					'CODE' => '<pre><code><xsl:apply-templates/></code></pre>'
				],
				[
					'root' => [],
					'tags' => [
						'CODE' => [
							'denyChild'                   => ['CODE'],
							'disableAutoLineBreaks'       => true,
							'ignoreTags'                  => true,
							'ignoreSurroundingWhitespace' => true,
							'trimFirstLine'               => true
						]
					]
				]
			],
		];
	}
}