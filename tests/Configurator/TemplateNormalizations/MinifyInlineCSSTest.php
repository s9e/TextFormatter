<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\MinifyInlineCSS
*/
class MinifyInlineCSSTest extends AbstractTestClass
{
	public static function getData()
	{
		return [
			[
				'<i style=" color : #123456 ; "/>',
				'<i style="color:#123456"/>'
			],
			[
				'<i style=" color:#1122aA; "/>',
				'<i style="color:#12a"/>'
			],
			[
				'<i style="color: #ABCDEF;"/>',
				'<i style="color:#abcdef"/>'
			],
			[
				'<i style="color: #FF0000;"/>',
				'<i style="color:red"/>'
			],
			[
				'<i style=" color: {@color} ; "/>',
				'<i style="color:{@color}"/>'
			],
			[
				'<i style="{\'color: #000000\'}"/>',
				'<i style="{\'color: #000000\'}"/>'
			],
			[
				'<i style="left: 0px; top: 0px"/>',
				'<i style="left:0;top:0"/>'
			],
			[
				'<i style="width: 10px; height: 10px;"/>',
				'<i style="width:10px;height:10px"/>'
			],
			[
				'<i style="background:#FF0000 url(#FF000)"/>',
				'<i style="background:red url(#FF000)"/>'
			],
		];
	}
}