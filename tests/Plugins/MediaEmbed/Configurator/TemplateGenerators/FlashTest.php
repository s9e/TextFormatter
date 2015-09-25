<?php

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed\Configurator\TemplateGenerators;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators\Flash;

/**
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerator
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators\Flash
*/
class FlashTest extends AbstractTest
{
	protected function getTemplateGenerator()
	{
		return new Flash;
	}

	public function getGetTemplateTests()
	{
		return [
			[
				[
					'src' => '/embed/{@id}'
				],
				'<div style="display:inline-block;width:100%;max-width:640px"><div style="position:relative;padding-bottom:56.25%"><object data="/embed/{@id}" style="height:100%;left:0;position:absolute;width:100%" type="application/x-shockwave-flash" typemustmatch=""><param name="allowfullscreen" value="true"/></object></div></div>'
			],
			[
				[
					'flashvars' => 'a=1',
					'src'       => '/embed/{@id}'
				],
				'<div style="display:inline-block;width:100%;max-width:640px"><div style="position:relative;padding-bottom:56.25%"><object data="/embed/{@id}" style="height:100%;left:0;position:absolute;width:100%" type="application/x-shockwave-flash" typemustmatch=""><param name="allowfullscreen" value="true"/><param name="flashvars" value="a=1"/></object></div></div>'
			],
		];
	}
}