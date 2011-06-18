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
	public function tagName_can_be_customized_at_loading_time()
	{
		$this->cb->loadPlugin('Paragrapher', null, array('tagName' => 'PARA'));

		$this->assertArrayMatches(
			array('tagName' => 'PARA'),
			$this->cb->Paragrapher->getConfig()
		);
	}
}