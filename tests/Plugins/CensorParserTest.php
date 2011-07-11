<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Tests\Test;

include_once __DIR__ . '/../Test.php';

/**
* @covers s9e\TextFormatter\Plugins\CensorParser
*/
class CensorParserTest extends Test
{
	public function testReplacesCensoredWordsWithDefaultReplacement()
	{
		$this->cb->Censor->addWord('apple');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <C>apple</C></rt>',
			'You dirty ****'
		);
	}

	/**
	* @depends testReplacesCensoredWordsWithDefaultReplacement
	*/
	public function testCanCensorUnicodeWords()
	{
		$this->cb->Censor->addWord('苹果');

		$this->assertTransformation(
			'You dirty 苹果',
			'<rt>You dirty <C>苹果</C></rt>',
			'You dirty ****'
		);
	}

	public function testReplacesCensoredWordsWithCustomReplacement()
	{
		$this->cb->Censor->addWord('apple', 'orange');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <C with="orange">apple</C></rt>',
			'You dirty orange'
		);
	}

	public function testCensoredWordsCanUseAnAsteriskAtTheStartAsAJoker()
	{
		$this->cb->Censor->addWord('*pple');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <C>apple</C></rt>',
			'You dirty ****'
		);
	}

	public function testCensoredWordsCanUseAnAsteriskInTheMiddleAsAJoker()
	{
		$this->cb->Censor->addWord('ap*e');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <C>apple</C></rt>',
			'You dirty ****'
		);
	}

	public function testCensoredWordsCanUseAnAsteriskAtTheEndAsAJoker()
	{
		$this->cb->Censor->addWord('*ple');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <C>apple</C></rt>',
			'You dirty ****'
		);
	}

	/**
	* @depends testCensoredWordsCanUseAnAsteriskAtTheStartAsAJoker
	*/
	public function testTheAsteriskCanMatchMultipleLetters()
	{
		$this->cb->Censor->addWord('*ple');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <C>apple</C></rt>',
			'You dirty ****'
		);
	}

	/**
	* @depends testCensoredWordsCanUseAnAsteriskAtTheStartAsAJoker
	*/
	public function testTheAsteriskCanMatchZeroLetters()
	{
		$this->cb->Censor->addWord('*apple');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <C>apple</C></rt>',
			'You dirty ****'
		);
	}

	/**
	* @depends testCensoredWordsCanUseAnAsteriskInTheMiddleAsAJoker
	*/
	public function testTheAsteriskCanMatchUnicodeLetters()
	{
		$this->cb->Censor->addWord('Pok*man');

		$this->assertTransformation(
			'You dirty Pokéman',
			'<rt>You dirty <C>Pokéman</C></rt>',
			'You dirty ****'
		);
	}

	public function testCensoredWordsCanUseAQuestionMarkAsAJokerForOneCharacter()
	{
		$this->cb->Censor->addWord('?pple');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <C>apple</C></rt>',
			'You dirty ****'
		);
	}

	public function testCensoredWordsCanUseAQuestionMarkAsAJokerForZeroCharacter()
	{
		$this->cb->Censor->addWord('appl?');

		$this->assertTransformation(
			'You dirty appl',
			'<rt>You dirty <C>appl</C></rt>',
			'You dirty ****'
		);
	}

	/**
	* @depends testCensoredWordsCanUseAQuestionMarkAsAJokerForOneCharacter
	*/
	public function testTheQuestionMarkDoesNotMatchMultipleCharacters()
	{
		$this->cb->Censor->addWord('?pple');

		$this->assertTransformation(
			'You dirty pineapple',
			'<pt>You dirty pineapple</pt>',
			'You dirty pineapple'
		);
	}

	public function testTheQuestionMarkCanMatchADigit()
	{
		$this->cb->Censor->addWord('Pok?man');

		$this->assertTransformation(
			'You dirty Pok3man',
			'<rt>You dirty <C>Pok3man</C></rt>',
			'You dirty ****'
		);
	}

	public function testTheQuestionMarkCanMatchASymbol()
	{
		$this->cb->Censor->addWord('Pok?man');

		$this->assertTransformation(
			'You dirty Pok#man',
			'<rt>You dirty <C>Pok#man</C></rt>',
			'You dirty ****'
		);
	}

	/**
	* @depends testCensoredWordsCanUseAQuestionMarkAsAJokerForOneCharacter
	*/
	public function testTheQuestionMarkCanMatchAnUnicodeLetter()
	{
		$this->cb->Censor->addWord('Pok?man');

		$this->assertTransformation(
			'You dirty Pokéman',
			'<rt>You dirty <C>Pokéman</C></rt>',
			'You dirty ****'
		);
	}

	/**
	* @depends testTheQuestionMarkCanMatchAnUnicodeLetter
	*/
	public function testCanReplaceCensoredUnicodeWordsWithCustomReplacement()
	{
		$this->cb->Censor->addWord('Pok?man', 'Pikaboy');

		$this->assertTransformation(
			'You dirty Pokéman',
			'<rt>You dirty <C with="Pikaboy">Pokéman</C></rt>',
			'You dirty Pikaboy'
		);
	}

	/**
	* @depends testTheQuestionMarkCanMatchAnUnicodeLetter
	*/
	public function testCensoredWordsAreCaseInsensitive()
	{
		$this->cb->Censor->addWord('Pokéman');

		$this->assertTransformation(
			'You dirty POKÉMAN',
			'<rt>You dirty <C>POKÉMAN</C></rt>',
			'You dirty ****'
		);
	}

	/**
	* @test
	* @depends testReplacesCensoredWordsWithDefaultReplacement
	*/
	public function Can_use_a_custom_tagName()
	{
		$this->cb->loadPlugin('Censor', null, array('tagName' => 'censored'));
		$this->cb->Censor->addWord('apple');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <CENSORED>apple</CENSORED></rt>',
			'You dirty ****'
		);
	}

	/**
	* @test
	* @depends testReplacesCensoredWordsWithCustomReplacement
	*/
	public function Can_use_a_custom_attrName()
	{
		$this->cb->loadPlugin('Censor', null, array('attrName' => 'replacement'));
		$this->cb->Censor->addWord('apple', 'orange');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <C replacement="orange">apple</C></rt>',
			'You dirty orange'
		);
	}

	/**
	* @test
	* @depends testReplacesCensoredWordsWithDefaultReplacement
	*/
	public function Can_use_a_custom_defaultReplacement()
	{
		$this->cb->loadPlugin('Censor', null, array('defaultReplacement' => '####'));
		$this->cb->Censor->addWord('apple');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <C>apple</C></rt>',
			'You dirty ####'
		);
	}

	public function testDoesNotAttemptToCreateItsTagIfItAlreadyExists()
	{
		$this->cb->loadPlugin('Censor');
		$this->cb->loadPlugin('Censor');
	}

	public function testTheDollarSymbolHasNoSpecialMeaningInReplacement()
	{
		$this->cb->Censor->addWord('apple', '$0');

		$this->assertTransformation(
			'Hello apple',
			'<rt>Hello <C with="$0">apple</C></rt>',
			'Hello $0'
		);
	}

	public function testTheBackslashHasNoSpecialMeaningInReplacement()
	{
		$this->cb->Censor->addWord('apple', '\\0');

		$this->assertTransformation(
			'Hello apple',
			'<rt>Hello <C with="\\0">apple</C></rt>',
			'Hello \\0'
		);
	}
}