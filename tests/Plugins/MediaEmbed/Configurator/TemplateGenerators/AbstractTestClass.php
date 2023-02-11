<?php

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed\Configurator\TemplateGenerators;

use s9e\TextFormatter\Tests\Test;

abstract class AbstractTestClass extends Test
{
	abstract public static function getGetTemplateTests();
	abstract protected function getTemplateGenerator();

	/**
	* @testdox getTemplate() tests
	* @dataProvider getGetTemplateTests
	*/
	public function testGetTemplate(array $attributes, $expected)
	{
		$templateGenerator = $this->getTemplateGenerator();
		$template          = $templateGenerator->getTemplate($attributes);
		$template          = $this->configurator->templateNormalizer->normalizeTemplate($template);

		$this->assertSame($expected, $template);
	}
}