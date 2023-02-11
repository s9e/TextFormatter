<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractChooseOptimization
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\OptimizeChoose
*/
class OptimizeChooseTest extends AbstractTestClass
{
	public static function getData()
	{
		return [
			[
				'<xsl:choose>
					<xsl:when test="@foo"/>
					<xsl:otherwise/>
				</xsl:choose>',
				''
			],
			[
				'<xsl:choose>
					<xsl:when test="@foo">foo</xsl:when>
				</xsl:choose>',
				'<xsl:if test="@foo">foo</xsl:if>'
			],
			[
				'<xsl:choose>
					<xsl:when test="@foo">foo</xsl:when>
					<xsl:otherwise/>
				</xsl:choose>',
				'<xsl:if test="@foo">foo</xsl:if>'
			],
			[
				'<xsl:choose>
					<xsl:when test="@foo"/>
					<xsl:otherwise>bar</xsl:otherwise>
				</xsl:choose>',
				'<xsl:if test="not(@foo)">bar</xsl:if>'
			],
			[
				'<xsl:choose>
					<xsl:when><br/>foo</xsl:when>
					<xsl:otherwise><br/>bar</xsl:otherwise>
				</xsl:choose>',
				'<br/><xsl:choose>
					<xsl:when>foo</xsl:when>
					<xsl:otherwise>bar</xsl:otherwise>
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when><br/><br/>foo</xsl:when>
					<xsl:otherwise><br/><br/>bar</xsl:otherwise>
				</xsl:choose>',
				'<br/><br/><xsl:choose>
					<xsl:when>foo</xsl:when>
					<xsl:otherwise>bar</xsl:otherwise>
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when>foo<br/><br/></xsl:when>
					<xsl:otherwise>bar<br/><br/></xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:when>foo</xsl:when>
					<xsl:otherwise>bar</xsl:otherwise>
				</xsl:choose><br/><br/>'
			],
			[
				'<xsl:choose>
					<xsl:when>foo<br/></xsl:when>
					<xsl:otherwise>bar<br/></xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:when>foo</xsl:when>
					<xsl:otherwise>bar</xsl:otherwise>
				</xsl:choose><br/>'
			],
			[
				'<xsl:choose>
					<xsl:when><div id="id">foo</div></xsl:when>
					<xsl:otherwise><div id="id">bar</div></xsl:otherwise>
				</xsl:choose>',
				'<div id="id"><xsl:choose>
					<xsl:when>foo</xsl:when>
					<xsl:otherwise>bar</xsl:otherwise>
				</xsl:choose></div>'
			],
			[
				'<xsl:choose>
					<xsl:when><div id="id">foo</div></xsl:when>
					<xsl:otherwise><span id="id">bar</span></xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:when><div id="id">foo</div></xsl:when>
					<xsl:otherwise><span id="id">bar</span></xsl:otherwise>
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when><div id="id" class="foo">foo</div></xsl:when>
					<xsl:otherwise><div id="id">bar</div></xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:when><div id="id" class="foo">foo</div></xsl:when>
					<xsl:otherwise><div id="id">bar</div></xsl:otherwise>
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when><br/></xsl:when>
					<xsl:otherwise><br/></xsl:otherwise>
				</xsl:choose>',
				'<br/>'
			],
			[
				'<xsl:choose>
					<xsl:when><br/></xsl:when>
					<xsl:otherwise><hr/></xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:when><br/></xsl:when>
					<xsl:otherwise><hr/></xsl:otherwise>
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when><blockquote><br id="a"/></blockquote></xsl:when>
					<xsl:otherwise><blockquote><br id="b"/><xsl:choose>
						<xsl:when><br id="c"/></xsl:when>
						<xsl:otherwise><br id="d"/></xsl:otherwise>
					</xsl:choose></blockquote></xsl:otherwise>
				</xsl:choose>',
				'<blockquote><xsl:choose>
					<xsl:when><br id="a"/></xsl:when>
					<xsl:otherwise><br id="b"/><xsl:choose>
						<xsl:when><br id="c"/></xsl:when>
						<xsl:otherwise><br id="d"/></xsl:otherwise>
					</xsl:choose></xsl:otherwise>
				</xsl:choose></blockquote>'
			],
			[
				'<xsl:choose>
					<xsl:when><xsl:choose>
						<xsl:when>x</xsl:when>
						<xsl:otherwise>y</xsl:otherwise>
					</xsl:choose></xsl:when>
					<xsl:otherwise><xsl:choose>
						<xsl:when>x</xsl:when>
						<xsl:otherwise>y</xsl:otherwise>
					</xsl:choose></xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
						<xsl:when>x</xsl:when>
						<xsl:otherwise>y</xsl:otherwise>
					</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:when test="@foo"><xsl:choose>
						<xsl:when test="@bar">A1</xsl:when>
						<xsl:otherwise>A2</xsl:otherwise>
					</xsl:choose></xsl:when>
					<xsl:otherwise><xsl:choose>
						<xsl:when test="@bar">B1</xsl:when>
						<xsl:otherwise>B2</xsl:otherwise>
					</xsl:choose></xsl:otherwise>
				</xsl:choose>',
				'<xsl:choose>
					<xsl:when test="@foo"><xsl:choose>
						<xsl:when test="@bar">A1</xsl:when>
						<xsl:otherwise>A2</xsl:otherwise>
					</xsl:choose></xsl:when>
					<xsl:otherwise><xsl:choose>
						<xsl:when test="@bar">B1</xsl:when>
						<xsl:otherwise>B2</xsl:otherwise>
					</xsl:choose></xsl:otherwise>
				</xsl:choose>'
			],
			[
				'<xsl:choose>
					<xsl:otherwise>xxx</xsl:otherwise>
				</xsl:choose>',
				'xxx'
			],
			[
				self::ws('<xsl:choose>
					<xsl:when test="$STYLE_ID=6">
						<xsl:choose>
							<xsl:when test="true()">
								<xsl:choose>
									<xsl:when test="true()">_</xsl:when>
								</xsl:choose>
							</xsl:when>
						</xsl:choose>
					</xsl:when>
					<xsl:otherwise>
						<xsl:choose>
							<xsl:when test="true()">
								<xsl:choose>
									<xsl:when test="true()">_</xsl:when>
								</xsl:choose>
							</xsl:when>
						</xsl:choose>
					</xsl:otherwise>
				</xsl:choose>'),
				self::ws('
					<xsl:if test="true()">
						<xsl:if test="true()">_</xsl:if>
					</xsl:if>
				')
			],
		];
	}
}