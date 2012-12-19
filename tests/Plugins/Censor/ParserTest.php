<?php

namespace s9e\TextFormatter\Tests\Plugins\Censor;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Censor\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Censor\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
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
				},
			),
			array(
				'apple',
				'<rt><FOO>apple</FOO></rt>',
				array('tagName' => 'FOO'),
				function ($constructor)
				{
					$constructor->Censor->add('apple');
				},
			),
			array(
				'apple',
				'<rt><CENSOR replacement="orange">apple</CENSOR></rt>',
				array('attrName' => 'replacement'),
				function ($constructor)
				{
					$constructor->Censor->add('apple', 'orange');
				},
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
				'You dirty 苹果',
				'You dirty orange',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('苹果', 'orange');
				}
			),
			array(
				'You dirty apple',
				'You dirty ****',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('*pple');
				}
			),
			array(
				'You dirty apple',
				'You dirty ****',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('ap*e');
				}
			),
			array(
				'You dirty apple',
				'You dirty ****',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('app*');
				}
			),
			array(
				'You dirty apple',
				'You dirty ****',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('*apple*');
				}
			),
			array(
				'You dirty Pokéman',
				'You dirty ****',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('pok*man');
				}
			),
			array(
				'You dirty Pok3man',
				'You dirty ****',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('pok?man');
				}
			),
			array(
				'You dirty Pok#man',
				'You dirty ****',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('pok?man');
				}
			),
			array(
				'You dirty Pokéman',
				'You dirty digiman',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('pok?man', 'digiman');
				}
			),
			array(
				'You dirty apple',
				'You dirty ****',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('?pple');
				}
			),
			array(
				'You dirty pineapple',
				'You dirty pineapple',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('?pple');
				}
			),
			array(
				'You dirty $0',
				'You dirty ****',
				array(),
				function ($constructor)
				{
					$constructor->Censor->add('$0');
				}
			),
		);
	}
}