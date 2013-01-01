<?php

namespace s9e\TextFormatter\Tests\Plugins\Censor;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Censor\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavascriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Censor\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavascriptRunner;
	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return array(
			array(
				'apple',
				'<rt><CENSOR>apple</CENSOR></rt>',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('apple');
				}
			),
			array(
				'apple',
				'<rt><FOO>apple</FOO></rt>',
				array('tagName' => 'FOO'),
				function ($constructor)
				{
					$constructor->Censor->add('apple');
				}
			),
			array(
				'apple',
				'<rt><CENSOR replacement="orange">apple</CENSOR></rt>',
				array('attrName' => 'replacement'),
				function ($constructor)
				{
					$constructor->Censor->add('apple', 'orange');
				}
			),
			array(
				'You dirty 苹果',
				'<rt>You dirty <CENSOR with="orange">苹果</CENSOR></rt>',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('苹果', 'orange');
				}
			),
			array(
				'You dirty apple',
				'<rt>You dirty <CENSOR>apple</CENSOR></rt>',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('*pple');
				}
			),
			array(
				'You dirty apple',
				'<rt>You dirty <CENSOR>apple</CENSOR></rt>',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('ap*e');
				}
			),
			array(
				'You dirty apple',
				'<rt>You dirty <CENSOR>apple</CENSOR></rt>',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('app*');
				}
			),
			array(
				'You dirty apple',
				'<rt>You dirty <CENSOR>apple</CENSOR></rt>',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('*apple*');
				}
			),
			array(
				'You dirty Pokéman',
				'<rt>You dirty <CENSOR>Pokéman</CENSOR></rt>',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('pok*man');
				}
			),
			array(
				'You dirty Pok3man',
				'<rt>You dirty <CENSOR>Pok3man</CENSOR></rt>',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('pok?man');
				}
			),
			array(
				'You dirty Pok#man',
				'<rt>You dirty <CENSOR>Pok#man</CENSOR></rt>',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('pok?man');
				}
			),
			array(
				'You dirty Pokéman',
				'<rt>You dirty <CENSOR with="digiman">Pokéman</CENSOR></rt>',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('pok?man', 'digiman');
				}
			),
			array(
				'You dirty apple',
				'<rt>You dirty <CENSOR>apple</CENSOR></rt>',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('?pple');
				}
			),
			array(
				'You dirty pineapple',
				'<pt>You dirty pineapple</pt>',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('?pple');
				}
			),
			array(
				'You dirty $0',
				'<rt>You dirty <CENSOR>$0</CENSOR></rt>',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('$0');
				}
			),
		);
	}

	public function getRenderingTests()
	{
		return array(
			array(
				'You dirty apple',
				'You dirty ****',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('apple');
				}
			),
			array(
				'You dirty apple',
				'You dirty orange',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('apple', 'orange');
				}
			),
			array(
				'You dirty apple',
				'You dirty ****',
				array('tagName' => 'FOO'),
				function ($constructor)
				{
					$constructor->Censor->add('apple');
				}
			),
			array(
				'You dirty apple',
				'You dirty orange',
				array('attrName' => 'replacement'),
				function ($constructor)
				{
					$constructor->Censor->add('apple', 'orange');
				}
			),
		);
	}
}