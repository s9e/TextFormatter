<?php

namespace s9e\TextFormatter\Tests\Configurator;

use s9e\TextFormatter\Configurator\JavaScript\ConfigOptimizer;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\JavaScript\ConfigOptimizer
*/
class ConfigOptimizerTest extends Test
{
	/**
	* @testdox reset() clears the stored objects
	*/
	public function testReset()
	{
		$optimizer = new ConfigOptimizer;
		$optimizer->optimizeObject(['xxxxxxxx']);
		$this->assertNotEmpty($optimizer->getObjects());
		$optimizer->reset();
		$this->assertEmpty($optimizer->getObjects());
	}

	/**
	* @testdox OptimizeObject tests
	* @dataProvider getOptimizeObjectTests
	*/
	public function testOptimizeObject($original, $expected, $objects)
	{
		$optimizer = new ConfigOptimizer;
		$this->assertEquals($expected, $optimizer->optimizeObject($original));
		$this->assertEquals(implode("\n", $objects), rtrim($optimizer->getObjects()));
	}

	public function getOptimizeObjectTests()
	{
		return [
			[
				[
					'foo' => [12345, 54321],
					'bar' => [12345, 54321]
				],
				'o5D6AC35D',
				[
					'/** @const */ var o3D7424E0=[12345,54321];',
					'/** @const */ var o5D6AC35D={bar:o3D7424E0,foo:o3D7424E0};'
				]
			],
			[
				new Dictionary([
					'foo' => [12345, 54321],
					'bar' => [12345, 54321]
				]),
				'oCA6E6DE0',
				[
					'/** @const */ var o3D7424E0=[12345,54321];',
					'/** @const */ var oCA6E6DE0={"bar":o3D7424E0,"foo":o3D7424E0};'
				]
			],
			[
				// Small literals are preserved
				[
					'foo' => [0],
					'bar' => [0]
				],
				'o46F1E3B8',
				[
					'/** @const */ var o46F1E3B8={bar:[0],foo:[0]};'
				]
			],
		];
	}
}