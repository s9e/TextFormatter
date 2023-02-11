<?php

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed\Configurator\TemplateGenerators;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators\Iframe;

/**
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerator
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\TemplateGenerators\Iframe
*/
class IframeTest extends AbstractTestClass
{
	protected function getTemplateGenerator()
	{
		return new Iframe;
	}

	public static function getGetTemplateTests()
	{
		return [
			[
				[
					'src' => '/embed/{@id}'
				],
				'<span style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="/embed/{@id}" style="border:0;height:100%;left:0;position:absolute;width:100%"/></span></span>'
			],
			[
				[
					'allow'     => 'geolocation',
					'onload'    => 'alert(1)',
					'scrolling' => '',
					'src'       => '/embed/{@id}',
					'style'     => [
						'border'     => 'solid green 2px',
						'box-shadow' => '10px 5px 5px black'
					]
				],
				'<span style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:56.25%"><iframe allow="geolocation" allowfullscreen="" loading="lazy" onload="alert(1)" scrolling="" src="/embed/{@id}" style="border:solid green 2px;box-shadow:10px 5px 5px black;height:100%;left:0;position:absolute;width:100%"/></span></span>'
			],
			[
				[
					'width'  => '100%',
					'height' => '186',
					'src'    => 'foo'
				],
				'<iframe allowfullscreen="" loading="lazy" scrolling="no" src="foo" style="border:0;height:186px;width:100%"/>'
			],
			[
				[
					'width'  => '{@width}',
					'height' => '{@height}',
					'src'    => 'foo'
				],
				'<span style="display:inline-block;width:100%;max-width:{@width}px"><span><xsl:attribute name="style">display:block;overflow:hidden;position:relative;<xsl:if test="@width&gt;0">padding-bottom:<xsl:value-of select="100*@height div@width"/>%</xsl:if></xsl:attribute><iframe allowfullscreen="" loading="lazy" scrolling="no" src="foo" style="border:0;height:100%;left:0;position:absolute;width:100%"/></span></span>'
			],
			[
				[
					'width'     => '100%',
					'max-width' => '800',
					'height'    => '186',
					'src'       => 'foo'
				],
				'<iframe allowfullscreen="" loading="lazy" scrolling="no" src="foo" style="border:0;height:186px;max-width:800px;width:100%"/>'
			],
			[
				[
					'width'  => '500',
					'height' => '186',
					'src'    => 'foo',
					'onload' => "this.style.height='200px'"
				],
				'<iframe allowfullscreen="" loading="lazy" onload="this.style.height=\'200px\'" scrolling="no" src="foo" style="border:0;height:186px;max-width:500px;width:100%"/>'
			],
			[
				[
					'width'  => '500',
					'height' => '300',
					'src'    => 'foo',
					'onload' => 'this.style.height=$height;this.style.width=$width'
				],
				'<iframe allowfullscreen="" loading="lazy" onload="this.style.height=$height;this.style.width=$width" scrolling="no" src="foo" style="border:0;height:300px;max-width:100%;width:500px"/>'
			],
			[
				[
					'width'          => '640',
					'height'         => '360',
					'padding-height' => '30',
					'src'            => 'foo'
				],
				'<span style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:60.9375%;padding-bottom:calc(56.25% + 30px)"><iframe allowfullscreen="" loading="lazy" scrolling="no" src="foo" style="border:0;height:100%;left:0;position:absolute;width:100%"/></span></span>'
			],
			[
				[
					'width'          => '640',
					'height'         => '360',
					'padding-height' => '30',
					'src'            => 'foo',
					'onload'         => '0<1'
				],
				'<span style="display:inline-block;width:100%;max-width:640px"><span style="display:block;overflow:hidden;position:relative;padding-bottom:60.9375%;padding-bottom:calc(56.25% + 30px)"><iframe allowfullscreen="" loading="lazy" onload="0&lt;1" scrolling="no" src="foo" style="border:0;height:100%;left:0;position:absolute;width:100%"/></span></span>'
			],
		];
	}
}