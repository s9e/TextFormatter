<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Tests\Test;

include_once __DIR__ . '/../../src/autoloader.php';

/**
* @covers s9e\TextFormatter\Plugins\HTMLEntitiesParser
*/
class HTMLEntitiesParserTest extends Test
{
	/**
	* @test
	*/
	public function Basic_entities_are_replaced()
	{
		$this->cb->loadPlugin('HTMLEntities');

		$this->assertTransformation(
			'AT&amp;T',
			'<rt>AT<HE char="&amp;">&amp;amp;</HE>T</rt>',
			'AT&amp;T'
		);
	}

	/**
	* @test
	* @depends Basic_entities_are_replaced
	*/
	public function Disabled_entities_are_ignored()
	{
		$this->cb->HTMLEntities->disableEntity('&amp;');

		$this->assertTransformation(
			'AT&amp;T',
			'<pt>AT&amp;amp;T</pt>',
			'AT&amp;amp;T'
		);
	}

	/**
	* @test
	*/
	public function Entities_are_replaced_by_their_UTF8_representation()
	{
		$this->cb->loadPlugin('HTMLEntities');

		$this->assertTransformation(
			'Pok&eacute;mon',
			'<rt>Pok<HE char="é">&amp;eacute;</HE>mon</rt>',
			'Pokémon'
		);
	}

	/**
	* @test
	*/
	public function Entities_are_case_sensitive()
	{
		$this->cb->loadPlugin('HTMLEntities');

		$this->assertTransformation(
			'POK&Eacute;MON',
			'<rt>POK<HE char="É">&amp;Eacute;</HE>MON</rt>',
			'POKÉMON'
		);
	}

	/**
	* @test
	*/
	public function Unknown_entities_are_ignored()
	{
		$this->cb->loadPlugin('HTMLEntities');

		$this->assertTransformation(
			'POK&EACUTE;MON',
			'<pt>POK&amp;EACUTE;MON</pt>',
			'POK&amp;EACUTE;MON'
		);
	}
}