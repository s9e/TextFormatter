<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser
*/
class FunctionalTest extends Test
{
	protected function runFunctionalTest($methodName, $original, $expected, $setup)
	{
		$setup($this->configurator);
		$this->$methodName($original, $expected);
	}

	/**
	* @testdox Functional tests
	* @dataProvider getFunctionalTests
	*/
	public function testFunctional($original, $expected, $setup)
	{
		$this->runFunctionalTest('assertParsing', $original, $expected, $setup);
	}

	/**
	* @group needs-js
	* @testdox Functional tests (JavaScript)
	* @dataProvider getFunctionalTests
	* @requires extension json
	* @covers s9e\TextFormatter\Configurator\JavaScript
	*/
	public function testFunctionalJavaScript($original, $expected, $setup)
	{
		$this->runFunctionalTest('assertJSParsing', $original, $expected, $setup);
	}

	public function getFunctionalTests()
	{
		return [
			[
				'xx[x].....[/x]xx',
				'<r>xx<X innerText="....." outerText="[x].....[/x]" tagText="[x]"><s>[x]</s>.....<e>[/x]</e></X>xx</r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->filterChain->append(__CLASS__ . '::addComputedValues')
						->resetParameters()
						->addParameterByName('tag')
						->addParameterByName('innerText')
						->addParameterByName('outerText')
						->addParameterByName('tagText')
						->setJS("
							function (tag, innerText, outerText, tagText)
							{
								tag.setAttributes({
									'innerText': innerText,
									'outerText': outerText,
									'tagText':   tagText
								});
							}
						");
					$configurator->BBCodes->add('X')->forceLookahead = true;
				}
			],
			[
				'xx[x].....[/x]xx',
				'<r>xx<X innerText="" outerText="[x]" tagText="[x]"><s>[x]</s>.....<e>[/x]</e></X>xx</r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->filterChain->append(__CLASS__ . '::addComputedValues')
						->resetParameters()
						->addParameterByName('tag')
						->addParameterByName('innerText')
						->addParameterByName('outerText')
						->addParameterByName('tagText')
						->setJS("
							function (tag, innerText, outerText, tagText)
							{
								tag.setAttributes({
									'innerText': innerText,
									'outerText': outerText,
									'tagText':   tagText
								});
							}
						");
					$configurator->BBCodes->add('X');
				}
			],
		];
	}

	public static function addComputedValues($tag, $innerText, $outerText, $tagText)
	{
		$tag->setAttributes([
			'innerText' => $innerText,
			'outerText' => $outerText,
			'tagText'   => $tagText
		]);
	}
}