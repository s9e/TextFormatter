<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\EnforceHTMLOmittedEndTags
*/
class EnforceHTMLOmittedEndTagsTest extends AbstractTestClass
{
	public static function getData()
	{
		return [
			[
				'<div><div>..</div><div>..</div></div>',
				'<div><div>..</div><div>..</div></div>'
			],
			[
				'<p><b>..</b><i>..</i></p>',
				'<p><b>..</b><i>..</i></p>',
			],
			[
				'<p>..<p>..</p></p>',
				'<p>..</p><p>..</p>',
			],
			[
				'<div><p>..<p>..<b>..</b></p></p></div>',
				'<div><p>..</p><p>..<b>..</b></p></div>',
			],
			[
				'<div><p>..<p>..</p><b>..</b></p></div>',
				'<div><p>..</p><p>..</p><b>..</b></div>',
			],
			[
				'<div><p>..<p>..</p></p><b>..</b></div>',
				'<div><p>..</p><p>..</p><b>..</b></div>',
			],
			[
				'<video><source src="1"><source src="2"></source></source></video>',
				'<video><source src="1"/><source src="2"/></video>'
			],
		];
	}
}