<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Runner
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\AbstractConvertor
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\BooleanOperators
*/
class BooleanOperatorsTest extends AbstractConvertorTest
{
	public function getConvertorTests()
	{
		return [
			// And
			[
				'1=1 and 2=2',
				'1==1&&2==2'
			],
			[
				"@tld='es' and boolean(\$AMAZON_ASSOCIATE_TAG_ES)",
				"\$node->getAttribute('tld')==='es'&&\$this->params['AMAZON_ASSOCIATE_TAG_ES']!==''"
			],
			// BooleanSub
			[
				'(1=0 or 1=1) and (2=2 or 1=1)',
				'(1==0||1==1)&&(2==2||1==1)'
			],
			// Or
			[
				'1=0 or 2=2',
				'1==0||2==2'
			],
		];
	}
}