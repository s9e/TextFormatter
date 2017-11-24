<?php

namespace s9e\TextFormatter\Tests\Parser\AttributeFilters;

use s9e\TextFormatter\Tests\Test;

abstract class AbstractFilterTest extends Test
{
	abstract public function getFilterTests();

	/**
	* @dataProvider getFilterTests
	*/
	public function testFilter($filter, $originalValue, $expectedValue, $expectedLogs = [], $setup = null)
	{
		$this->runFilterTest(false, $filter, $originalValue, $expectedValue, $expectedLogs, $setup);
	}

	/**
	* @dataProvider getFilterTests
	* @group needs-js
	*/
	public function testJSFilter($filter, $originalValue, $expectedValue, $expectedLogs = [], $setup = null)
	{
		$this->runFilterTest(true, $filter, $originalValue, $expectedValue, $expectedLogs, $setup);
	}

	protected function runFilterTest($js, $filter, $originalValue, $expectedValue, $expectedLogs, $setup)
	{
		$this->configurator->BBCodes->add('x');
		$attribute = $this->configurator->tags->add('x')->attributes->add('x');
		$attribute->filterChain->add($filter);
		$attribute->required = false;

		if (isset($setup))
		{
			$setup($this->configurator);
		}

		$text = '[x="' . addcslashes($originalValue, '\\\'"') . '"/]';

		$expectedXml = '<r><X' . (($expectedValue === false) ? '' : ' x="' . htmlspecialchars($expectedValue) . '"') . '>' . htmlspecialchars($text, ENT_NOQUOTES) . '</X></r>';

		if ($js)
		{
			$this->assertJSParsing($text, $expectedXml);
		}
		else
		{
			$parser = $this->getParser();
			$logger = $parser->getLogger();

			$actualXml  = $parser->parse($text);
			$actualLogs = $logger->getLogs();

			foreach ($actualLogs as &$entry)
			{
				// Remove irrelevant data from the logs
				unset($entry[2]['attrName']);
				unset($entry[2]['tag']);
			}
			unset($entry);

			$this->assertSame($expectedXml,  $actualXml);
			$this->assertSame($expectedLogs, $actualLogs);
		}
	}
}