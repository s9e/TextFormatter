<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP;

use s9e\TextFormatter\Configurator\RendererGenerators\PHP\BranchOutputOptimizer;
use s9e\TextFormatter\Tests\Test;

/**
* @requires extension tokenizer
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\BranchOutputOptimizer
*/
class BranchOutputOptimizerTest extends Test
{
	/**
	* @dataProvider getBranchOutputOptimizationTests
	* @testdox optimize() tests
	*/
	public function testBranchOutputOptimization($original, $expected)
	{
		$optimizer = new BranchOutputOptimizer;

		$php      = preg_replace('((?:^|\\n)\\s*)', '', $original);
		$expected = preg_replace('((?:^|\\n)\\s*)', '', $expected);

		$this->assertSame($expected, $optimizer->optimize(token_get_all('<?php ' . $php)));
	}

	public static function getBranchOutputOptimizationTests()
	{
		return [
			[
				"if(1){\$this->out.='<b>';foo();\$this->out.='</b>';}
				  else{\$this->out.='<b>';bar();\$this->out.='</b>';}",

				"\$this->out.='<b>';
				if(1){foo();}
				 else{bar();}
				\$this->out.='</b>';"
			],
			[
				"before();
				if(1){\$this->out.='<b>';foo();\$this->out.='</b>';}
				 else{\$this->out.='<b>';bar();\$this->out.='</b>';}
				after();",

				"before();
				\$this->out.='<b>';
				if(1){foo();}
				 else{bar();}
				\$this->out.='</b>';
				after();"
			],
			[
				"if(1){\$this->out.='<b>';\$this->out.=\$foo;\$this->out.='</b>';}
				  else{\$this->out.='<b>';\$this->out.=\$bar;\$this->out.='</b>';}",

				"\$this->out.='<b>';
				if(1){\$this->out.=\$foo;}
				 else{\$this->out.=\$bar;}
				\$this->out.='</b>';"
			],
			[
				"if(1){\$this->out.='<b>'.\$foo.'</b>';}
				  else{\$this->out.='<b>'.\$bar.'</b>';}",

				"\$this->out.='<b>';
				if(1){\$this->out.=\$foo;}
				 else{\$this->out.=\$bar;}
				\$this->out.='</b>';"
			],
			[
				"if(1){\$this->out.='<b>'.htmlspecialchars(\$foo).'</b>';}
				  else{\$this->out.='<b>'.htmlspecialchars(trim(\$bar)).'</b>';}",

				"\$this->out.='<b>';
				if(1){\$this->out.=htmlspecialchars(\$foo);}
				 else{\$this->out.=htmlspecialchars(trim(\$bar));}
				\$this->out.='</b>';"
			],
			[
				"if(1){\$this->out.='<b>';foo();\$this->out.='</b>';}\$this->out.='<br/>';}",
				"if(1){\$this->out.='<b>';foo();\$this->out.='</b>';}\$this->out.='<br/>';}"
			],
			[
				"   if(foo()){\$this->out.='<b>'.\$text;}
				elseif(bar()){\$this->out.='<u>'.\$text;}
				         else{\$this->out.=\$text;}",

				"   if(foo()){\$this->out.='<b>';}
				elseif(bar()){\$this->out.='<u>';}
				              \$this->out.=\$text;"
			],
			[
				"if(t1()){
					if(foo()){\$this->out.='t1:'.\$text;}
					     else{\$this->out.=\$text;}
				}
				else{
					if(foo()){\$this->out.='t2:'.\$text;}
					     else{\$this->out.=\$text;}
				}",

				"if(t1()){
					if(foo()){\$this->out.='t1:';}
				}
				else{
					if(foo()){\$this->out.='t2:';}
				}
				\$this->out.=\$text;"
			],
			[
				"if(t1()){
					if(foo()){\$this->out.=\$text.':t1';}
					     else{\$this->out.=\$text;}
				}
				else{
					if(foo()){\$this->out.=\$text.':t2';}
					     else{\$this->out.=\$text;}
				}",

				"\$this->out.=\$text;
				if(t1()){
					if(foo()){\$this->out.=':t1';}
				}
				else{
					if(foo()){\$this->out.=':t2';}
				}"
			],
			[
				"if(t1()){
					\$this->out.='a:';
					if(foo()){\$this->out.='b:'.\$foo;}
					     else{\$this->out.='b:'.\$bar;}
				}
				else{
					\$this->out.='a:';
					if(foo()){\$this->out.='b:'.\$baz;}
					     else{\$this->out.='b:'.\$quux;}
				}",

				"\$this->out.='a:b:';
				if(t1()){
					if(foo()){\$this->out.=\$foo;}
					     else{\$this->out.=\$bar;}
				}
				else{
					if(foo()){\$this->out.=\$baz;}
					     else{\$this->out.=\$quux;}
				}"
			],
			[
				"if(t1()){
					x();
					if(foo()){\$this->out.='b:'.\$foo;}
					     else{\$this->out.='b:'.\$bar;}
				}
				else{
					x();
					if(foo()){\$this->out.='b:'.\$foo;}
					     else{\$this->out.='b:'.\$bar;}
				}",

				"if(t1()){
					x();
					\$this->out.='b:';
					if(foo()){\$this->out.=\$foo;}
					     else{\$this->out.=\$bar;}
				}
				else{
					x();
					\$this->out.='b:';
					if(foo()){\$this->out.=\$foo;}
					     else{\$this->out.=\$bar;}
				}"
			],
			[
				"if(1){\$this->out.='<b>';foreach(foo()as\$foo){}\$this->out.='</b>';}
				  else{\$this->out.='<b>';foreach(foo()as\$foo){}\$this->out.='</b>';}",

				"\$this->out.='<b>';
				if(1){foreach(foo()as\$foo){}}
				 else{foreach(foo()as\$foo){}}
				\$this->out.='</b>';"
			],
		];
	}
}