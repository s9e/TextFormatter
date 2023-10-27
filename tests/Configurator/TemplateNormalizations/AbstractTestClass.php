<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

use Exception;
use ReflectionClass;
use s9e\SweetDOM\Document;
use s9e\TextFormatter\Configurator\Helpers\TemplateLoader;
use s9e\TextFormatter\Tests\Test;

abstract class AbstractTestClass extends Test
{
	protected function getNormalizer(array $args = [])
	{
		$className  = preg_replace(
			'/.*\\\\(.*?)Test$/',
			's9e\\TextFormatter\\Configurator\\TemplateNormalizations\\\\$1',
			get_class($this)
		);
		$reflection = new ReflectionClass($className);

		return $reflection->newInstanceArgs($args);
	}

	/**
	* @testdox Works
	* @dataProvider getData
	*/
	public function test($template, $expected, array $args = [])
	{
		if ($expected instanceof Exception)
		{
			$this->expectException(get_class($expected));
			if ($expected->getMessage())
			{
				$this->expectExceptionMessage($expected->getMessage());
			}
		}

		$xml = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $template
		     . '</xsl:template>';

		$dom = new Document;
		$dom->loadXML($xml);

		$this->getNormalizer($args)->normalize($dom->documentElement);

		$this->assertSame(
			$expected,
			TemplateLoader::save($dom)
		);
	}

	abstract public static function getData();
}