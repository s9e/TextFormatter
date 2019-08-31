<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\RemoveLivePreviewAttributes
*/
class RemoveLivePreviewAttributesTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				'<div data-s9e-mediaembed="foo">..</div>',
				'<div data-s9e-mediaembed="foo">..</div>'
			],
			[
				'<div data-s9e-livepreview-onrender="this.foo()">..</div>',
				'<div>..</div>'
			],
			[
				'<div data-s9e-livepreview-ignore-attrs="style">..</div>',
				'<div>..</div>'
			],
			[
				'<div data-s9e-mediaembed="foo" data-s9e-livepreview-onrender="this.foo()">..</div>',
				'<div data-s9e-mediaembed="foo">..</div>'
			],
			[
				'<div data-s9e-mediaembed="foo">..</div>',
				'<div data-s9e-mediaembed="foo">..</div>'
			],
			[
				'<div><xsl:attribute name="data-s9e-livepreview-onrender">this.foo()</xsl:attribute>..</div>',
				'<div>..</div>'
			],
		];
	}
}