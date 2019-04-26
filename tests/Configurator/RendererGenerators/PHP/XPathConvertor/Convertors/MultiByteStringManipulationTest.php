<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Runner
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\AbstractConvertor
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\MultiByteStringManipulation
*/
class MultiByteStringManipulationTest extends AbstractConvertorTest
{
	public function getConvertorTests()
	{
		return [
			// Substring
			[
				"substring('ö÷ø', 2, 1)",
				"mb_substr('ö÷ø',1,1,'utf-8')"
			],
			[
				"substring('ö÷ø', 2)",
				"mb_substr('ö÷ø',1,null,'utf-8')"
			],
			[
				"substring(@foo, 1, string-length(@foo))",
				"mb_substr(\$node->getAttribute('foo'),0,max(0,preg_match_all('(.)su',\$node->getAttribute('foo'))),'utf-8')"
			],
			[
				"substring(@foo, string-length(@foo))",
				"mb_substr(\$node->getAttribute('foo'),max(0,preg_match_all('(.)su',\$node->getAttribute('foo'))-1),null,'utf-8')"
			],
			[
				'substring(.,1,2)',
				"mb_substr(\$node->textContent,0,2,'utf-8')"
			],
			[
				'substring(.,@x,1)',
				"mb_substr(\$node->textContent,max(0,\$node->getAttribute('x')-1),1,'utf-8')"
			],
			[
				'substring(.,1,@x)',
				"mb_substr(\$node->textContent,0,max(0,\$node->getAttribute('x')),'utf-8')"
			],
			[
				'substring(.,2)',
				"mb_substr(\$node->textContent,1,null,'utf-8')"
			],
		];
	}
}