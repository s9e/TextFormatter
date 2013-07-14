<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\RemoveInterElementWhitespace
*/
class RemoveInterElementWhitespaceTest extends AbstractTest
{
	public function getData()
	{
		return [
			[
				'<div>
					<b>
						<xsl:apply-templates/>
					</b>
				</div>',
				'<div><b><xsl:apply-templates/></b></div>'
			],
			[
				'<div>
					<b>foo</b> <i>bar</i>
				</div>',
				'<div><b>foo</b> <i>bar</i></div>'
			],
			[
				'<div>
					<xsl:text>  </xsl:text>
				</div>',
				'<div><xsl:text>  </xsl:text></div>'
			],
		];
	}
}