<?php

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed\Configurator\TemplateGenerators;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators\Iframe;

/**
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerator
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators\Iframe
*/
class IframeTest extends AbstractTest
{
	protected function getTemplateGenerator()
	{
		return new Iframe;
	}

	public function getGetTemplateTests()
	{
		return [
			[
				[
					'src' => '/embed/{@id}'
				],
				'<div style="display:inline-block;width:100%;max-width:640px"><div style="overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" scrolling="no" src="/embed/{@id}" style="border:0;height:100%;left:0;position:absolute;width:100%"/></div></div>'
			],
			[
				[
					'onload'    => 'alert(1)',
					'scrolling' => '',
					'src'       => '/embed/{@id}',
					'style'     => [
						'border'     => 'solid green 2px',
						'box-shadow' => '10px 5px 5px black'
					]
				],
				'<div style="display:inline-block;width:100%;max-width:640px"><div style="overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" onload="alert(1)" scrolling="" src="/embed/{@id}" style="border:solid green 2px;box-shadow:10px 5px 5px black;height:100%;left:0;position:absolute;width:100%"/></div></div>'
			],
			[
				[
					'width'  => '100%',
					'height' => '186',
					'src'    => 'foo'
				],
				'<iframe allowfullscreen="" scrolling="no" src="foo" style="border:0;height:186px;width:100%"/>'
			],
			[
				[
					'width'  => '{@width}',
					'height' => '{@height}',
					'src'    => 'foo'
				],
				'<div style="display:inline-block;width:100%;max-width:{@width}px"><div><xsl:attribute name="style">overflow:hidden;position:relative;<xsl:if test="@width&gt;0">padding-bottom:<xsl:value-of select="100*@height div@width"/>%</xsl:if></xsl:attribute><iframe allowfullscreen="" scrolling="no" src="foo" style="border:0;height:100%;left:0;position:absolute;width:100%"/></div></div>'
			],
			[
				[
					'width'     => '100%',
					'max-width' => '800',
					'height'    => '186',
					'src'       => 'foo'
				],
				'<iframe allowfullscreen="" scrolling="no" src="foo" style="border:0;height:186px;max-width:800px;width:100%"/>'
			],
			[
				[
					'width'  => '500',
					'height' => '186',
					'src'    => 'foo',
					'onload' => "this.style.height='200px'"
				],
				'<iframe allowfullscreen="" onload="this.style.height=\'200px\'" scrolling="no" src="foo" style="border:0;height:186px;max-width:500px;width:100%"/>'
			],
			[
				[
					'width'          => '640',
					'height'         => '360',
					'padding-height' => '30',
					'src'            => 'foo'
				],
				'<div style="display:inline-block;width:100%;max-width:640px"><div style="overflow:hidden;position:relative;padding-bottom:60.9375%;padding-bottom:calc(56.25% + 30px)"><iframe allowfullscreen="" scrolling="no" src="foo" style="border:0;height:100%;left:0;position:absolute;width:100%"/></div></div>'
			],
			[
				[
					'width'          => '640',
					'height'         => '360',
					'padding-height' => '30',
					'src'            => 'foo',
					'onload'         => '0<1'
				],
				'<div style="display:inline-block;width:100%;max-width:640px"><div style="overflow:hidden;position:relative;padding-bottom:60.9375%;padding-bottom:calc(56.25% + 30px)"><iframe allowfullscreen="" onload="0&lt;1" scrolling="no" src="foo" style="border:0;height:100%;left:0;position:absolute;width:100%"/></div></div>'
			],
		];
	}
}