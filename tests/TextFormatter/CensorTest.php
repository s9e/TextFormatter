<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../Test.php';

/**
* covers s9e\Toolkit\TextFormatter\Plugins\CensorConfig
* covers s9e\Toolkit\TextFormatter\Plugins\CensorParser
*/
class CensorTest extends Test
{
	public function testCensorPluginIsOptimizedAwayIfNoWordsAreAdded()
	{
		$this->cb->loadPlugin('Censor');

		$this->assertArrayNotHasKey(
			'Censor',
			$this->cb->getPluginsConfig()
		);
	}

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

	/**
	* @depends testReplacesCensoredWordsWithDefaultReplacement
	*/
	public function testDefaultReplacementCanBeReplacedAtLoadingTime()
	{
		$this->cb->loadPlugin('Censor', null, array('defaultReplacement' => '####'));
		$this->cb->Censor->addWord('apple');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <C>apple</C></rt>',
			'You dirty ####'
		);
	}

	/**
	* @depends testReplacesCensoredWordsWithDefaultReplacement
	*/
	public function testTagNameCanBeReplacedAtLoadingTime()
	{
		$this->cb->loadPlugin('Censor', null, array('tagName' => 'censored'));
		$this->cb->Censor->addWord('apple');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <CENSORED>apple</CENSORED></rt>',
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

	/**
	* @depends testReplacesCensoredWordsWithCustomReplacement
	*/
	public function testAttributeNameCanBeReplacedAtLoadingTime()
	{
		$this->cb->loadPlugin('Censor', null, array('attrName' => 'replacement'));
		$this->cb->Censor->addWord('apple', 'orange');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <C replacement="orange">apple</C></rt>',
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

	public function testCensoredWordsCanUseAQuestionMarkAsAJokerForOneLetter()
	{
		$this->cb->Censor->addWord('?pple');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <C>apple</C></rt>',
			'You dirty ****'
		);
	}

	/**
	* @depends testCensoredWordsCanUseAQuestionMarkAsAJokerForOneLetter
	*/
	public function testTheQuestionMarkDoesNotMatchMultipleLetters()
	{
		$this->cb->Censor->addWord('?pple');

		$this->assertTransformation(
			'You dirty pineapple',
			'<pt>You dirty pineapple</pt>',
			'You dirty pineapple'
		);
	}

	/**
	* @depends testCensoredWordsCanUseAQuestionMarkAsAJokerForOneLetter
	*/
	public function testTheQuestionMarkDoesNotMatchZeroLetters()
	{
		$this->cb->Censor->addWord('?pple');

		$this->assertTransformation(
			'You dirty pple',
			'<pt>You dirty pple</pt>',
			'You dirty pple'
		);
	}

	/**
	* @depends testCensoredWordsCanUseAQuestionMarkAsAJokerForOneLetter
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
	public function testCanCensoredUnicodeWordsWithCustomReplacement()
	{
		$this->cb->Censor->addWord('Pok?man', 'Pikaboy');

		$this->assertTransformation(
			'You dirty Pokéman',
			'<rt>You dirty <C with="Pikaboy">Pokéman</C></rt>',
			'You dirty Pikaboy'
		);
	}
}