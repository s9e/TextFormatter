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
				'<rt><CENSOR>apple</CENSOR></rt>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('apple');
				}
			],
			[
				'apple',
				'<rt><FOO>apple</FOO></rt>',
				['tagName' => 'FOO'],
				function ($constructor)
				{
					$constructor->Censor->add('apple');
				}
			],
			[
				'apple',
				'<rt><CENSOR replacement="orange">apple</CENSOR></rt>',
				['attrName' => 'replacement'],
				function ($constructor)
				{
					$constructor->Censor->add('apple', 'orange');
				}
			],
			[
				'You dirty 苹果',
				'<rt>You dirty <CENSOR with="orange">苹果</CENSOR></rt>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('苹果', 'orange');
				}
			],
			[
				'You dirty apple',
				'<rt>You dirty <CENSOR>apple</CENSOR></rt>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('*pple');
				}
			],
			[
				'You dirty apple',
				'<rt>You dirty <CENSOR>apple</CENSOR></rt>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('ap*e');
				}
			],
			[
				'You dirty apple',
				'<rt>You dirty <CENSOR>apple</CENSOR></rt>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('app*');
				}
			],
			[
				'You dirty apple',
				'<rt>You dirty <CENSOR>apple</CENSOR></rt>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('*apple*');
				}
			],
			[
				'You dirty Pokéman',
				'<rt>You dirty <CENSOR>Pokéman</CENSOR></rt>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('pok*man');
				}
			],
			[
				'You dirty Pok3man',
				'<rt>You dirty <CENSOR>Pok3man</CENSOR></rt>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('pok?man');
				}
			],
			[
				'You dirty Pok#man',
				'<rt>You dirty <CENSOR>Pok#man</CENSOR></rt>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('pok?man');
				}
			],
			[
				'You dirty Pokéman',
				'<rt>You dirty <CENSOR with="digiman">Pokéman</CENSOR></rt>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('pok?man', 'digiman');
				}
			],
			[
				'You dirty apple',
				'<rt>You dirty <CENSOR>apple</CENSOR></rt>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('?pple');
				}
			],
			[
				'You dirty pineapple',
				'<pt>You dirty pineapple</pt>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('?pple');
				}
			],
			[
				'You dirty $0',
				'<rt>You dirty <CENSOR>$0</CENSOR></rt>',
				[],
				function ($constructor)
				{
					$constructor->Censor->add('$0');
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