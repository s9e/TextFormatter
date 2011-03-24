<?php

namespace s9e\Toolkit\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder;

include_once __DIR__ . '/../src/TextFormatter/ConfigBuilder.php';

abstract class Test extends \PHPUnit_Framework_TestCase
{
	public function __get($k)
	{
		switch ($k)
		{
			case 'cb':
				return $this->cb = new ConfigBuilder;

			case 'parser':
				return $this->parser = $this->cb->getParser();

			case 'renderer':
				return $this->renderer = $this->cb->getRenderer();

			default:
				throw new RuntimeException("Bad __get('$k')");
		}
	}

	protected function assertArrayMatches(array $expected, array $actual, $removeNull = true)
	{
		$this->reduceAndSortArrays($expected, $actual, $removeNull);
		$this->assertSame($expected, $actual);
	}

	protected function reduceAndSortArrays(array &$expected, array &$actual, $removeNull = true)
	{
		if (empty($expected))
		{
			return;
		}

		ksort($expected);
		ksort($actual);

		$actual = array_intersect_key($actual, $expected);

		foreach ($actual as $k => &$v)
		{
			if (is_array($expected[$k]) && is_array($v))
			{
				$this->reduceAndSortArrays($expected[$k], $v, $removeNull);
			}
		}

		/**
		* Remove null values from $expected, they indicate that the key should NOT appear in $actual
		*/
		if ($removeNull)
		{
			foreach (array_keys($expected, null, true) as $k)
			{
				unset($expected[$k]);
			}
		}
	}

	protected function assertArrayHasNestedKeys($array)
	{
		$keys = array_slice(func_get_args(), 1);

		$this->assertInternalType('array', $array);

		foreach ($keys as $key)
		{
			$this->assertArrayHasKey($key, $array);
			$array =& $array[$key];
		}
	}

	protected function assertParsing($text, $expectedXml, $expectedLog = array('error' => null))
	{
		$actualXml = $this->parser->parse($text);
		$actualLog = $this->parser->getLog();

		if (!isset($expectedLog['debug']))
		{
			unset($actualLog['debug']);
		}

		$this->assertXmlStringEqualsXmlString($expectedXml, $actualXml);
		$this->assertArrayMatches($expectedLog, $actualLog);
	}

	protected function assertRendering($text, $expectedHtml, $expectedLog = array('error' => null))
	{
		$actualXml = $this->parser->parse($text);
		$actualLog = $this->parser->getLog();

		$this->assertArrayMatches($expectedLog, $actualLog);

		$actualHtml = $this->renderer->render($actualXml);
		$this->assertSame($expectedHtml, $actualHtml);
	}

	protected function assertTransformation($text, $expectedXml, $expectedHtml, $expectedLog = array('error' => null))
	{
		$actualXml = $this->parser->parse($text);
		$actualLog = $this->parser->getLog();

		$this->assertArrayMatches($expectedLog, $actualLog);
		$this->assertXmlStringEqualsXmlString($expectedXml, $actualXml);

		$actualHtml = $this->renderer->render($actualXml);
		$this->assertSame($expectedHtml, $actualHtml);

		$revertedText = html_entity_decode(strip_tags($actualXml));
		$this->assertSame($text, $revertedText, 'Could not revert to plain text');
	}
}