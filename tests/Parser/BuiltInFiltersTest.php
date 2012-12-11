<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Parser\BuiltInFilters;
use s9e\TextFormatter\Parser\FilterProcessing;
use s9e\TextFormatter\Parser\Logger;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser\BuiltInFilters
*/
class BuiltInFiltersTest extends Test
{
	protected static function filterTestdox($filterName, array $filterOptions, $original, $expected)
	{
		$testdox = '#' . $filterName;

		if ($filterOptions)
		{
			$testdox .= ' [';
			foreach ($filterOptions as $k => $v)
			{
				$testdox .= "'$k'=>$v,";
			}
			$testdox = substr($testdox, 0, -1) . ']';
		}

		if ($expected === false)
		{
			$testdox .= ' rejects ' . var_export($original, true);
		}
		elseif ($expected === $original)
		{
			$testdox .= ' accepts ' . var_export($original, true);
		}
		else
		{
			$testdox .= ' transforms ' . var_export($original, true)
					  . ' into ' . var_export($expected, true);
		}

		return $testdox;
	}

	/**
	* @dataProvider getRegressionsData
	* @testdox Regression tests
	*/
	public function testRegressions($original, array $results)
	{
		foreach ($results as $filterName => $expected)
		{
			$methodName = 'filter' . ucfirst($filterName);
			$testdox = self::filterTestdox($filterName, array(), $original, $expected);

			$this->assertSame(
				$expected,
				BuiltInFilters::$methodName($original),
				'Failed asserting that ' . $testdox
			);
		}
	}

	/**
	* @dataProvider getData
	*/
	public function test($filterName, $original, $expected, array $filterOptions = array(), array $logs = array())
	{
		$testdox = self::filterTestdox($filterName, $filterOptions, $original, $expected);

		$logger = new Logger;

		$this->assertSame(
			$expected,
			Hax::filterValue($original, $filterName, $filterOptions, $logger),
			'Failed asserting that ' . $testdox
		);

		$this->assertSame($logs, $logger->get(), "Logs don't match");
	}

	public function getData()
	{
		return array(
			array('range', '2', 2, array('min' => 2, 'max' => 5)),
			array('range', '5', 5, array('min' => 2, 'max' => 5)),
			array('range', '-5', -5, array('min' => -5, 'max' => 5)),
			array('range', '1', 2, array('min' => 2, 'max' => 5), array(
				array('warn', 'Value outside of range, adjusted up to min value', array(
					'attrValue' => 1, 'min' => 2, 'max' => 5
				))
			)),
			array('range', '10', 5, array('min' => 2, 'max' => 5), array(
				array('warn', 'Value outside of range, adjusted down to max value', array(
					'attrValue' => 10, 'min' => 2, 'max' => 5
				))
			)),
			array('range', '5x', false, array('min' => 2, 'max' => 5)),
			array('url', 'http://www.älypää.com', 'http://www.xn--lyp-plada.com'),
			array(
				'url',
				'http://en.wikipedia.org/wiki/Matti_Nykänen', 'http://en.wikipedia.org/wiki/Matti_Nyk%C3%A4nen'
			),
			array(
				'url',
				'http://user:pass@en.wikipedia.org:80/wiki/Matti_Nykänen?foo&bar#baz', 'http://user:pass@en.wikipedia.org:80/wiki/Matti_Nyk%C3%A4nen?foo&bar#baz'
			),
			array(
				'url',
				'http://älypää.com:älypää.com@älypää.com',
				'http://%C3%A4lyp%C3%A4%C3%A4.com:%C3%A4lyp%C3%A4%C3%A4.com@xn--lyp-plada.com'
			),
			array('url', '*invalid*', false),
			array('url', 'http://www.example.com', 'http://www.example.com'),
		);
	}

	/**
	* NOTE: this test is not normative. Some cases exist solely to track regressions or changes in
	*       behaviour in ext/filter
	*/
	public function getRegressionsData()
	{
		return array(
			array('123', array('int' => 123, 'uint' => 123, 'float' => 123.0, 'number' => '123')),
			array('123abc', array('int' => false, 'uint' => false, 'float' => false, 'number' => false)),
			array('0123', array('int' => false, 'uint' => false, 'float' => 123.0, 'number' => '0123')),
			array('-123', array('int' => -123, 'uint' => false, 'float' => -123.0, 'number' => false)),
			array('12.3', array('int' => false, 'uint' => false, 'float' => 12.3, 'number' => false)),
			array('10000000000000000000', array('int' => false, 'uint' => false, 'float' => 10000000000000000000, 'number' => '10000000000000000000')),
			array('12e3', array('int' => false, 'uint' => false, 'float' => 12000.0, 'number' => false)),
			array('-12e3', array('int' => false, 'uint' => false, 'float' => -12000.0, 'number' => false)),
			array('12e-3', array('int' => false, 'uint' => false, 'float' => 0.012, 'number' => false)),
			array('-12e-3', array('int' => false, 'uint' => false, 'float' => -0.012, 'number' => false)),
			array('0x123', array('int' => false, 'uint' => false, 'float' => false, 'number' => false)),
		);
	}
}

class Hax
{
	use FilterProcessing;

	public static function filterValue($attrValue, $filterName, array $filterOptions, Logger $logger)
	{
		$configurator = new Configurator;
		$configurator
			->tags->add('FOO')
			->attributes->add('foo')
			->filterChain->append('#' . $filterName, $filterOptions);

		$config = $configurator->asConfig();
		$config['registeredVars']['logger'] = $logger;

		return self::executeFilter(
			$config['tags']['FOO']['attributes']['foo']['filterChain'][0],
			array(
				'attrName'       => 'foo',
				'attrValue'      => $attrValue,
				'registeredVars' => $config['registeredVars']
			)
		);
	}
}