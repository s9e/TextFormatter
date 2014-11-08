<?php

namespace s9e\TextFormatter\Tests\Plugins\Censor;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Censor\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Censor\Parser
*/
class ParserTest extends Test
{
	/**
	* @testdox Parsing tests
	* @dataProvider getParsingTests
	*/
	public function testParsing($original, $expected, array $pluginOptions = array(), $setup = null, $expectedJS = null, $assertMethod = 'assertSame')
	{
		$pluginName = preg_replace('/.*\\\\([^\\\\]+)\\\\.*/', '$1', get_class($this));

		$configurator = new Configurator;
		$plugin = $configurator->plugins->load($pluginName, $pluginOptions);

		if ($setup)
		{
			$setup($configurator, $plugin);
		}

		$this->$assertMethod($expected, $configurator->getParser()->parse($original));
	}

	/**
	* @group needs-js
	* @testdox Parsing tests (JavaScript)
	* @dataProvider getParsingTests
	* @requires extension json
	* @covers s9e\TextFormatter\Configurator\JavaScript
	*/
	public function testJavaScriptParsing($original, $expected, array $pluginOptions = array(), $setup = null, $expectedJS = null, $assertMethod = 'assertSame')
	{
		if (isset($expectedJS))
		{
			$expected = $expectedJS;
		}

		$pluginName = preg_replace('/.*\\\\([^\\\\]+)\\\\.*/', '$1', get_class($this));

		$plugin = $this->configurator->plugins->load($pluginName, $pluginOptions);

		if ($setup)
		{
			$setup($this->configurator, $plugin);
		}

		$this->assertJSParsing($original, $expected);
	}

	/**
	* @requires extension xsl
	* @testdox Parsing+rendering tests
	* @dataProvider getRenderingTests
	*/
	public function testRendering($original, $expected, array $pluginOptions = array(), $setup = null, $assertMethod = 'assertSame')
	{
		$pluginName = preg_replace('/.*\\\\([^\\\\]+)\\\\.*/', '$1', get_class($this));

		$plugin = $this->configurator->plugins->load($pluginName, $pluginOptions);

		if ($setup)
		{
			$setup($this->configurator, $plugin);
		}

		extract($this->configurator->finalize());

		$this->$assertMethod($expected, $renderer->render($parser->parse($original)));
	}

	public function getParsingTests()
	{
		return array(
			array(
				'apple',
				'<r><CENSOR>apple</CENSOR></r>',
				array(),
				function ($configurator)
				{
					$configurator->Censor->add('apple');
				}
			),
			array(
				'apple',
				'<r><FOO>apple</FOO></r>',
				array('tagName' => 'FOO'),
				function ($configurator)
				{
					$configurator->Censor->add('apple');
				}
			),
			array(
				'apple',
				'<r><CENSOR replacement="orange">apple</CENSOR></r>',
				array('attrName' => 'replacement'),
				function ($configurator)
				{
					$configurator->Censor->add('apple', 'orange');
				}
			),
			array(
				'You dirty 苹果',
				'<r>You dirty <CENSOR with="orange">苹果</CENSOR></r>',
				array(),
				function ($configurator)
				{
					$configurator->Censor->add('苹果', 'orange');
				}
			),
			array(
				'You dirty apple',
				'<r>You dirty <CENSOR>apple</CENSOR></r>',
				array(),
				function ($configurator)
				{
					$configurator->Censor->add('*pple');
				}
			),
			array(
				'You dirty apple',
				'<r>You dirty <CENSOR>apple</CENSOR></r>',
				array(),
				function ($configurator)
				{
					$configurator->Censor->add('ap*e');
				}
			),
			array(
				'You dirty apple',
				'<r>You dirty <CENSOR>apple</CENSOR></r>',
				array(),
				function ($configurator)
				{
					$configurator->Censor->add('app*');
				}
			),
			array(
				'You dirty apple',
				'<r>You dirty <CENSOR>apple</CENSOR></r>',
				array(),
				function ($configurator)
				{
					$configurator->Censor->add('*apple*');
				}
			),
			array(
				'You dirty Pokéman',
				'<r>You dirty <CENSOR>Pokéman</CENSOR></r>',
				array(),
				function ($configurator)
				{
					$configurator->Censor->add('pok*man');
				}
			),
			array(
				'You dirty Pok3man',
				'<r>You dirty <CENSOR>Pok3man</CENSOR></r>',
				array(),
				function ($configurator)
				{
					$configurator->Censor->add('pok*man');
				}
			),
			array(
				'You dirty Pok3man',
				'<r>You dirty <CENSOR>Pok3man</CENSOR></r>',
				array(),
				function ($configurator)
				{
					$configurator->Censor->add('pok?man');
				}
			),
			array(
				'You dirty Pok#man',
				'<r>You dirty <CENSOR>Pok#man</CENSOR></r>',
				array(),
				function ($configurator)
				{
					$configurator->Censor->add('pok?man');
				}
			),
			array(
				'You dirty Pokéman',
				'<r>You dirty <CENSOR with="digiman">Pokéman</CENSOR></r>',
				array(),
				function ($configurator)
				{
					$configurator->Censor->add('pok?man', 'digiman');
				}
			),
			array(
				'You dirty apple',
				'<r>You dirty <CENSOR>apple</CENSOR></r>',
				array(),
				function ($configurator)
				{
					$configurator->Censor->add('?pple');
				}
			),
			array(
				'You dirty pineapple',
				'<t>You dirty pineapple</t>',
				array(),
				function ($configurator)
				{
					$configurator->Censor->add('?pple');
				}
			),
			array(
				'You dirty $0',
				'<r>You dirty <CENSOR>$0</CENSOR></r>',
				array(),
				function ($configurator)
				{
					$configurator->Censor->add('$0');
				}
			),
			array(
				'You dirty A P P L E',
				'<r>You dirty <CENSOR>A P P L E</CENSOR></r>',
				array(),
				function ($configurator)
				{
					$configurator->Censor->add('a p p l e');
				}
			),
			array(
				'You dirty apple',
				'<r>You dirty <CENSOR>apple</CENSOR></r>',
				array(),
				function ($configurator)
				{
					$configurator->Censor->add('a p p l e');
				}
			),
			array(
				"Don't be such a Scunthorpe problem, thorpy",
				"<r>Don't be such a Scunthorpe problem, <CENSOR>thorpy</CENSOR></r>",
				array(),
				function ($configurator)
				{
					$configurator->Censor->add('*thorp*');
					$configurator->Censor->allow('scunthorpe');
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
				function ($configurator)
				{
					$configurator->Censor->add('apple');
				}
			),
			array(
				'You dirty apple',
				'You dirty orange',
				array(),
				function ($configurator)
				{
					$configurator->Censor->add('apple', 'orange');
				}
			),
			array(
				'You dirty apple',
				'You dirty ****',
				array('tagName' => 'FOO'),
				function ($configurator)
				{
					$configurator->Censor->add('apple');
				}
			),
			array(
				'You dirty apple',
				'You dirty orange',
				array('attrName' => 'replacement'),
				function ($configurator)
				{
					$configurator->Censor->add('apple', 'orange');
				}
			),
		);
	}
}