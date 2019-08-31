<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\RenameLivePreviewEvent
*/
class RenameLivePreviewEventTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				'<div data-s9e-livepreview-foo="foo"/>',
				'<div data-s9e-livepreview-foo="foo"/>'
			],
			[
				'<div data-s9e-livepreview-postprocess="foo"/>',
				'<div data-s9e-livepreview-onrender="foo"/>'
			],
			[
				'<xsl:attribute name="title"/>',
				'<xsl:attribute name="title"/>'
			],
			[
				'<xsl:attribute name="data-s9e-livepreview-postprocess"/>',
				'<xsl:attribute name="data-s9e-livepreview-onrender"/>'
			],
		];
	}
}