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

	public static function getOptimizationTests()
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
}