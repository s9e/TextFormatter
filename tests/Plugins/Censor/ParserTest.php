<?php

namespace s9e\TextFormatter\Tests\Plugins\Censor;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Censor\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Censor\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return [
			[
				'apple',
				'<r><CENSOR>apple</CENSOR></r>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('apple');
				}
			],
			[
				'apple',
				'<r><FOO>apple</FOO></r>',
				['tagName' => 'FOO'],
				function ($constructor)
				{
					$constructor->Censor->add('apple');
				}
			],
			[
				'apple',
				'<r><CENSOR replacement="orange">apple</CENSOR></r>',
				['attrName' => 'replacement'],
				function ($constructor)
				{
					$constructor->Censor->add('apple', 'orange');
				}
			],
			[
				'You dirty 苹果',
				'<r>You dirty <CENSOR with="orange">苹果</CENSOR></r>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('苹果', 'orange');
				}
			],
			[
				'You dirty apple',
				'<r>You dirty <CENSOR>apple</CENSOR></r>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('*pple');
				}
			],
			[
				'You dirty apple',
				'<r>You dirty <CENSOR>apple</CENSOR></r>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('ap*e');
				}
			],
			[
				'You dirty apple',
				'<r>You dirty <CENSOR>apple</CENSOR></r>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('app*');
				}
			],
			[
				'You dirty apple',
				'<r>You dirty <CENSOR>apple</CENSOR></r>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('*apple*');
				}
			],
			[
				'You dirty Pokéman',
				'<r>You dirty <CENSOR>Pokéman</CENSOR></r>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('pok*man');
				}
			],
			[
				'You dirty Pok3man',
				'<r>You dirty <CENSOR>Pok3man</CENSOR></r>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('pok*man');
				}
			],
			[
				'You dirty Pok3man',
				'<r>You dirty <CENSOR>Pok3man</CENSOR></r>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('pok?man');
				}
			],
			[
				'You dirty Pok#man',
				'<r>You dirty <CENSOR>Pok#man</CENSOR></r>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('pok?man');
				}
			],
			[
				'You dirty Pokéman',
				'<r>You dirty <CENSOR with="digiman">Pokéman</CENSOR></r>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('pok?man', 'digiman');
				}
			],
			[
				'You dirty apple',
				'<r>You dirty <CENSOR>apple</CENSOR></r>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('?pple');
				}
			],
			[
				'You dirty pineapple',
				'<t>You dirty pineapple</t>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('?pple');
				}
			],
			[
				'You dirty $0',
				'<r>You dirty <CENSOR>$0</CENSOR></r>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('$0');
				}
			],
			[
				'You dirty A P P L E',
				'<r>You dirty <CENSOR>A P P L E</CENSOR></r>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('a p p l e');
				}
			],
			[
				'You dirty apple',
				'<r>You dirty <CENSOR>apple</CENSOR></r>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('a p p l e');
				}
			],
		];
	}

	public function getRenderingTests()
	{
		return [
			[
				'You dirty apple',
				'You dirty ****',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('apple');
				}
			],
			[
				'You dirty apple',
				'You dirty orange',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('apple', 'orange');
				}
			],
			[
				'You dirty apple',
				'You dirty ****',
				['tagName' => 'FOO'],
				function ($constructor)
				{
					$constructor->Censor->add('apple');
				}
			],
			[
				'You dirty apple',
				'You dirty orange',
				['attrName' => 'replacement'],
				function ($constructor)
				{
					$constructor->Censor->add('apple', 'orange');
				}
			],
		];
	}
}