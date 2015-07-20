<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\FixUnescapedCurlyBracesInHtmlAttributes
*/
class FixUnescapedCurlyBracesInHtmlAttributesTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				'<hr title="foo"/>',
				'<hr title="foo"/>'
			],
			[
				'<hr title="{@foo}"/>',
				'<hr title="{@foo}"/>'
			],
			[
				'<hr onmouseover="if(1){alert(1)}"/>',
				'<hr onmouseover="if(1){{alert(1)}"/>'
			],
			[
				'<hr onmouseover="if(1){{alert(1)}"/>',
				'<hr onmouseover="if(1){{alert(1)}"/>'
			],
			[
				'<hr onmouseover="if(1){alert(1)}else{alert(0)}"/>',
				'<hr onmouseover="if(1){{alert(1)}else{{alert(0)}"/>'
			],
			[
				// Do not escape {@cmd}
				'<hr onmouseover="if(1){@cmd}(1)"/>',
				'<hr onmouseover="if(1){@cmd}(1)"/>',
			],
			[
				'<xsl:value-of select="\'{}\'"/>',
				'<xsl:value-of select="\'{}\'"/>'
			],
		];
	}
}