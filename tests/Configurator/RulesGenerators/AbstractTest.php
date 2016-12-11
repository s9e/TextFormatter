<?php

namespace s9e\TextFormatter\Tests\Configurator\RulesGenerators;

use s9e\TextFormatter\Configurator\Helpers\TemplateInspector;
use s9e\TextFormatter\Tests\Test;

abstract class AbstractTest extends Test
{
	public function assertBooleanRules($template, $expected)
	{
		$className = get_class($this);
		$className = 's9e\\TextFormatter\\Configurator\\RulesGenerators'
		           . substr($className, strrpos($className, '\\'), -4);

		$rulesGenerator = new $className;
		$this->assertEquals(
			$expected,
			$rulesGenerator->generateBooleanRules(new TemplateInspector($template))
		);
	}

	public function assertTargetedRules($src, $trg, $expected)
	{
		$className = get_class($this);
		$className = 's9e\\TextFormatter\\Configurator\\RulesGenerators'
		           . substr($className, strrpos($className, '\\'), -4);

		$rulesGenerator = new $className;
		$this->assertEquals(
			$expected,
			$rulesGenerator->generateTargetedRules(
				new TemplateInspector($src),
				new TemplateInspector($trg)
			)
		);
	}
}