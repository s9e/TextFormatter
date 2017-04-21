<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

use DOMDocument;
use Exception;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Tests\Test;

abstract class AbstractTest extends Test
{
	protected function getNormalizer()
	{
		$className  = preg_replace(
			'/.*\\\\(.*?)Test$/',
			's9e\\TextFormatter\\Configurator\\TemplateNormalizations\\\\$1',
			get_class($this)
		);

		return new $className;
	}

	/**
	* @testdox Works
	* @dataProvider getData
	*/
	public function test($template, $expected)
	{
		if ($expected instanceof Exception)
		{
			$this->setExpectedException(get_class($expected), $expected->getMessage());
		}

		$xml = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $template
		     . '</xsl:template>';

		$dom = new DOMDocument;
		$dom->loadXML($xml);

		$this->getNormalizer()->normalize($dom->documentElement);

		$this->assertSame(
			$expected,
			TemplateHelper::saveTemplate($dom)
		);
	}

	abstract public function getData();
}