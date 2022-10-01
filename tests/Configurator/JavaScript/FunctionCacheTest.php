<?php

namespace s9e\TextFormatter\Tests\Configurator;

use s9e\TextFormatter\Configurator\JavaScript\FunctionCache;
use s9e\TextFormatter\Tests\Test;

/**
* @requires extension json
* @covers s9e\TextFormatter\Configurator\JavaScript\FunctionCache
*/
class FunctionCacheTest extends Test
{
	/**
	* @dataProvider getAddFromXSLTests
	*/
	public function testAddFromXSL($template, $expected)
	{
		$xsl = '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"><xsl:template>' . $template . '</xsl:template></xsl:stylesheet>';

		$functionCache = new FunctionCache;
		$functionCache->addFromXSL($xsl);

		$this->assertEquals($expected, $functionCache->getJSON());
	}

	public function getAddFromXSLTests()
	{
		return [
			[
				'<hr data-s9e-livepreview-onupdate="alert(1)"/>',
				'{"167969434":/**@this {!Element}*/function(){alert(1);}}'
			],
			[
				'<hr data-s9e-livepreview-onupdate="alert(1)" data-s9e-livepreview-onrender="if(1){{alert(1);}}"/>',
				'{"167969434":/**@this {!Element}*/function(){alert(1);},"721683742":/**@this {!Element}*/function(){if(1){alert(1);}}}'
			],
			[
				'<hr data-s9e-livepreview-onrender="if(1){{alert(1);}}" data-s9e-livepreview-onupdate="alert(1)"/>',
				'{"167969434":/**@this {!Element}*/function(){alert(1);},"721683742":/**@this {!Element}*/function(){if(1){alert(1);}}}'
			],
			[
				'<iframe data-s9e-livepreview-onrender="alert(1)"/>',
				'{"167969434":/**@this {!HTMLIFrameElement}*/function(){alert(1);}}'
			],
			[
				'<iframe data-s9e-livepreview-onrender="alert(1)"/><script data-s9e-livepreview-onrender="alert(1)"/>',
				'{"167969434":/**@this {!HTMLIFrameElement|!HTMLScriptElement}*/function(){alert(1);}}'
			],
			[
				// Do not cache AVT
				'<hr data-s9e-livepreview-onupdate="alert({@x})"/>',
				'{}'
			],
		];
	}
}