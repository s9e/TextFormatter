<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP;

use s9e\TextFormatter\Configurator\RendererGenerators\PHP\ControlStructuresOptimizer;
use s9e\TextFormatter\Tests\Test;

/**
* @requires extension tokenizer
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\AbstractOptimizer
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\ControlStructuresOptimizer
*/
class ControlStructuresOptimizerTest extends Test
{
	/**
	* @dataProvider getControlStructureOptimizationTests
	* @testdox optimize() tests
	*/
	public function testControlStructureOptimization($original, $expected)
	{
		$optimizer = new ControlStructuresOptimizer;

		$this->assertSame($expected, $optimizer->optimize($original));
	}

	public static function getControlStructureOptimizationTests()
	{
		return [
			[
				'if ($foo) { bar(); }',
				'if ($foo) bar();'
			],
			[
				'if(($foo)){bar();}',
				'if(($foo))bar();'
			],
			[
				'if ($foo) { bar(); } else { baz(); }',
				'if ($foo) bar(); else baz();'
			],
			[
				'if($foo){bar();}else{ baz();}',
				'if($foo)bar();else baz();'
			],
			[
				'if($foo){bar();}else {baz();}',
				'if($foo)bar();else baz();'
			],
			[
				'if($foo){bar();}else{baz();}',
				'if($foo)bar();else baz();'
			],
			[
				'if($foo){bar();}else{$baz=1;}',
				'if($foo)bar();else$baz=1;'
			],
			[
				'if ($foo) { bar(); baz(); }',
				'if ($foo) { bar(); baz(); }'
			],
			[
				'while (--$i) { }',
				'while (--$i);'
			],
			[
				'while (--$i) { /* do nothing */ }',
				'while (--$i) /* do nothing */;'
			],
			[
				'if ($foo)
				{
					if ($bar)
					{
						bar();
					}
					elseif ($baz)
					{
						baz();
					}
					else
					{
						nope();
					}
				}',
				'if ($foo)
					if ($bar)
						bar();
					elseif ($baz)
						baz();
					else
						nope();'
			],
			[
				'if(1){if(2){}else{a();}}',
				'if(1)if(2);else a();'
			],
			[
				'if(1){if(2){}}else{a();}',
				'if(1){if(2);}else a();'
			],
			[
				'if(1){if(2){}} else{a();}',
				'if(1){if(2);} else a();'
			],
			[
				'if(1){if(2){if(3){}}}else{a();}',
				'if(1){if(2)if(3);}else a();'
			],
			[
				'if(1){if(2){if(3){}else{a();}}else{b();}',
				'if(1){if(2)if(3);else a();else b();'
			],
			[
				'if(1){while(0){}}else{a();}',
				'if(1)while(0);else a();'
			],
			[
				'do{a();}while(1);',
				'do{a();}while(1);'
			],
			[
				'foreach($foo as $bar){}',
				'foreach($foo as $bar);'
			],
			[
				'for ($i = 0; $i < ; ++$i){}',
				'for ($i = 0; $i < ; ++$i);'
			],
			[
				'if (1)
				{
					// foo
					foo();
				}',
				'if (1)
					// foo
					foo();'
			],
			[
				'if (1)
				{
					/**
					* @foo
					*/
					foo();
				}',
				'if (1)
					/**
					* @foo
					*/
					foo();'
			],
			[
				'if (1) {} else { if (2) { a(); b(); } c(); }',
				'if (1); else { if (2) { a(); b(); } c(); }'
			],
			[
				'if (1) {} else { if (2) {} }',
				'if (1); elseif (2);'
			],
			[
				'if (1) {} else { if (2) {} if (3) {} }',
				'if (1); else { if (2); if (3); }'
			],
			[
				'if (1)
				{
					foo();
				}
				else
				{
					// Comment
					if (1)
					{
						bar();
					}
					else
					{
						/**
						* Line 1
						* Line 2
						*/
						baz();
					}
				}',
				'if (1)
					foo();
				// Comment
				elseif (1)
					bar();
				else
					/**
					* Line 1
					* Line 2
					*/
					baz();'
			],
			[
				'if($foo){}else{if($foo){bar();}}',
				'if($foo);elseif($foo)bar();'
			],
			[
				'if($foo)
				{
				}
				else
				{
					if($bar)
					{
					}
					else
					{
						if($baz)
						{
							baz();
							baz();
						}
					}
				}',
				'if($foo);
				elseif($bar);
				elseif($baz)
				{
					baz();
					baz();
				}'
			],
		];
	}
}