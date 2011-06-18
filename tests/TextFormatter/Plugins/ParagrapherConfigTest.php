<?php

namespace s9e\Toolkit\Tests\TextFormatter\Plugins;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Plugins\ParagrapherConfig
*/
class ParagrapherConfigTest extends Test
{
	/**
	* @test
	*/
	public function paragraphTagName_can_be_customized_at_loading_time()
	{
		$this->cb->loadPlugin('Paragrapher', null, array('paragraphTagName' => 'PARA'));

		$this->assertArrayMatches(
			array('paragraphTagName' => 'PARA'),
			$this->cb->Paragrapher->getConfig()
		);
	}

	/**
	* @test
	*/
	public function linebreakTagName_can_be_customized_at_loading_time()
	{
		$this->cb->loadPlugin('Paragrapher', null, array('linebreakTagName' => 'LB'));

		$this->assertArrayMatches(
			array('linebreakTagName' => 'LB'),
			$this->cb->Paragrapher->getConfig()
		);
	}

	public function testDoesNotAttemptToCreateItsParagraphTagIfItAlreadyExists()
	{
		$this->cb->loadPlugin('Paragrapher');
		unset($this->cb->Paragrapher);
		$this->cb->loadPlugin('Paragrapher');
	}

	public function testDoesNotAttemptToCreateItsLinebreakTagIfItAlreadyExists()
	{
		$this->cb->loadPlugin('Paragrapher');
		unset($this->cb->Paragrapher);
		$this->cb->loadPlugin('Paragrapher');
	}

	public function testDoesNotAttemptToCreateItsLinebreakTagIfFalse()
	{
		$this->cb->loadPlugin('Paragrapher', null, array('linebreakTagName' => false));
	}
}