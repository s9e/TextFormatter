<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\Items\Template;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\TemplateDocument
*/
class TemplateDocumentTest extends Test
{
	/**
	* @testdox saveChanges() updates the document's original template
	*/
	public function testSaveChanges()
	{
		$template = new Template('<hr/>');

		$dom = $template->asDOM();
		$dom->documentElement->firstChild->setAttribute('id', 'x');
		$dom->saveChanges();

		$this->assertEquals('<hr id="x"/>', $template);
	}
}