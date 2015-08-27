<?php

namespace s9e\TextFormatter\Tests\Configurator;

use s9e\TextFormatter\Configurator\JavaScript\ConfigOptimizer;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\JavaScript\Encoder;
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
		$optimizer = new ConfigOptimizer(new Encoder);
		$optimizer->optimize([['xyz'],['xyz']]);
		$this->assertNotEmpty($optimizer->getVarDeclarations());
		$optimizer->reset();
		$this->assertEmpty($optimizer->getVarDeclarations());
	}

	/**
	* @testdox OptimizeObject tests
	* @dataProvider getOptimizeObjectTests
	*/
	public function testOptimizeObject($original, $expected, $objects)
	{
		$encoder   = new Encoder;
		$optimizer = new ConfigOptimizer($encoder);
		$config    = $optimizer->optimize($original);

		$this->assertSame($expected, $encoder->encode($config));
		$this->assertSame(implode("\n", $objects), rtrim($optimizer->getVarDeclarations()));
	}

	public function getOptimizeObjectTests()
	{
		return [
			[
				[
					'foo' => [12345, 54321],
					'bar' => [12345, 54321]
				],
				'{bar:o3D7424E0,foo:o3D7424E0}',
				[
					'/** @const */ var o3D7424E0=[12345,54321];'
				]
			],
			[
				new Dictionary([
					'foo' => [12345, 54321],
					'bar' => [12345, 54321]
				]),
				'{"bar":o3D7424E0,"foo":o3D7424E0}',
				[
					'/** @const */ var o3D7424E0=[12345,54321];'
				]
			],
			[
				[
					'foo' => [12345, ],
					'bar' => [54321, 12345]
				],
				'{bar:[54321,12345],foo:[12345]}',
				[]
			],
		];
	}
}