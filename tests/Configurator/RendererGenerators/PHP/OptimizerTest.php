<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP;

use s9e\TextFormatter\Configurator\RendererGenerators\PHP\Optimizer;
use s9e\TextFormatter\Tests\Test;

/**
* @requires extension tokenizer
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\Optimizer
*/
class OptimizerTest extends Test
{
	/**
	* @dataProvider getOptimizationTests
	* @testdox optimize() tests
	*/
	public function testOptimizations($original, $expected)
	{
		$optimizer = new Optimizer;

		$this->assertSame($expected, $optimizer->optimize($original));
	}

	public function getOptimizationTests()
	{
		return [
			[
				"\$this->out.='<br';\$this->out.='>';",
				"\$this->out.='<br>';"
			],
			[
				"\$this->out.='a';\$this->out.='b';\$this->out.='c';",
				"\$this->out.='abc';"
			],
			[
				"\$foo.='foo';\$bar.='foo';\$this->out.='b';",
				"\$foo.='foo';\$bar.='foo';\$this->out.='b';",
			],
			[
				"\$this->out.='foo';if(1){}",
				"\$this->out.='foo';if(1){}"
			],
			[
				"\$this->out.='foo'.'bar';",
				"\$this->out.='foobar';"
			],
			[
				"\$this->out.='foo'.\"bar\";",
				"\$this->out.='foo'.\"bar\";"
			],
			[
				"htmlspecialchars(\$node->getAttribute('foo'),2).htmlspecialchars(\$node->getAttribute('bar'),2)",
				"htmlspecialchars(\$node->getAttribute('foo').\$node->getAttribute('bar'),2)"
			],
			[
				"htmlspecialchars(\$node->getAttribute('foo'),1).htmlspecialchars(\$node->getAttribute('bar'),2)",
				"htmlspecialchars(\$node->getAttribute('foo'),1).htmlspecialchars(\$node->getAttribute('bar'),2)"
			],
			[
				"htmlspecialchars(\$node->getAttribute('foo'),2).'bar'",
				"htmlspecialchars(\$node->getAttribute('foo'),2).'bar'"
			],
			[
				"\$this->out.=htmlspecialchars('<>\"',2);",
				"\$this->out.='&lt;&gt;&quot;';"
			],
			[
				"\$this->out.=htmlspecialchars('<>\"',0);",
				"\$this->out.='&lt;&gt;\"';"
			],
			[
				'$this->out.=htmlspecialchars($node->localName,0);',
				'$this->out.=$node->localName;',
			],
			[
				'$this->out.=htmlspecialchars($node->nodeName,0);',
				'$this->out.=$node->nodeName;',
			],
		];
	}

	/**
	* @dataProvider getControlStructureOptimizationTests
	* @testdox optimize() tests
	*/
	public function testControlStructureOptimization($original, $expected)
	{
		$optimizer = new Optimizer;

		$this->assertSame($expected, $optimizer->optimizeControlStructures($original));
	}

	public function getControlStructureOptimizationTests()
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
		];
	}
}