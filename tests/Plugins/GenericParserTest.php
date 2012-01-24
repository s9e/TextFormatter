<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Tests\Test;

include_once __DIR__ . '/../../src/autoloader.php';

/**
* @covers s9e\TextFormatter\Plugins\GenericParser
*/
class GenericParserTest extends Test
{
	/**
	* @test
	*/
	public function Handles_attributes()
	{
		$this->cb->Generic->addReplacement(
			'#(?<href>http://[^\\s]+)#',
			'<a href="{@href}"><xsl:apply-templates /></a>'
		);

		$this->assertParsing(
			'This is an http://www.example.com/ URL.',
			'<rt>This is an <GF531C803 href="http://www.example.com/">http://www.example.com/</GF531C803> URL.</rt>'
		);
	}
}