<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Bundles;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Bundle;

class UNIT3D extends Bundle
{
	/**
	* @var array
	*/
	protected $definitions = [
		[
			'[h1]{TEXT}[/h1]',
			'<h1>{TEXT}</h1>'
		],
		[
			'[h2]{TEXT}[/h2]',
			'<h2>{TEXT}</h2>'
		],
		[
			'[h3]{TEXT}[/h3]',
			'<h3>{TEXT}</h3>'
		],
		[
			'[h4]{TEXT}[/h4]',
			'<h4>{TEXT}</h4>'
		],
		[
			'[h5]{TEXT}[/h5]',
			'<h5>{TEXT}</h5>'
		],
		[
			'[h6]{TEXT}[/h6]',
			'<h6>{TEXT}</h6>'
		],
		[
			'[b]{TEXT}[/b]',
			'<span style="font-weight: bold;">{TEXT}</span>'
		],
		[
			'[i]{TEXT}[/i]',
			'<em>{TEXT}</em>'
		],
		[
			'[u]{TEXT}[/u]',
			'<u>{TEXT}</u>'
		],
		[
			'[s]{TEXT}[/s]',
			'<span style="text-decoration: line-through;">{TEXT}</span>'
		],
		[
			'[size={NUMBER}]{TEXT}[/size]',
			'<span style="font-size: {NUMBER}px;">{TEXT}</span>'
		],
		[
			'[font={FONTFAMILY}]{TEXT}[/font]',
			'<span style="font-family: {FONTFAMILY};">{TEXT}</span>'
		],
		[
			'[color={COLOR}]{TEXT}[/color]',
			'<span style="color: {COLOR};">$2</span>'
		],
		[
			'[center]{TEXT}[/center]',
			'<div style="text-align:center;">{TEXT}</div>'
		],
		[
			'[left]{TEXT}[/left]',
			'<div style="text-align:left;">{TEXT}</div>'
		],
		[
			'[right]{TEXT}[/right]',
			'<div style="text-align:right;">{TEXT}</div>'
		],
		[
			'[quote name={TEXT1?}]{TEXT}[/quote]',
			'<ul class="media-list comments-list">
			<li class="media" style="border-left-width: 5px; border-left-style: solid; border-left-color: rgb(1, 188, 140);">
			<div class="media-body">
			<xsl:if test="@name">
				<strong><span><i class="fas fa-quote-left"></i> Quoting $1 :</span></strong>
			</xsl:if>
			<div class="pt-5">{TEXT}</div>
			</div>
			</li>
			</ul>'
		],
		[
			'[url url={URL;useContent}]{TEXT}[/url]',
			'<a href="{@url}">{TEXT}</a>'
		],
		[
			// @width is the default attribute, not @src
			'[img width={NUMBER?} src={URL;useContent}]',
			'<img src="{@src}"><xsl:copy-of select="@width"/></img>'
		],
		[
			'[list type={IDENTIFIER?}]{TEXT}[/list]',
			'<xsl:choose>
				<xsl:when test="@type = \'1\'">
					<ol><xsl:apply-templates/></ol>
				</xsl:when>
				<xsl:when test="@type = \'a\'">
					<ol type="a"><xsl:apply-templates/></ol>
				</xsl:when>
				<xsl:otherwise>
					<ul><xsl:apply-templates/></ul>
				</xsl:otherwise>
			</xsl:choose>'
		],
		[
			'[* $tagName=LI]{TEXT}[/*]',
			'<li>{TEXT}</li>'
		],
		[
			'[code]{TEXT}[/code]',
			'<pre>{TEXT}</pre>'
		],
		[
			'[alert]{TEXT}[/alert]',
			'<div class="bbcode-alert">{TEXT}</div>'
		],
		[
			'[note]{TEXT}[/note]',
			'<div class="bbcode-note">{TEXT}</div>'
		],
		[
			'[sub]{TEXT}[/sub]',
			'<sub>{TEXT}</sub>'
		],
		[
			'[sup]{TEXT}[/sup]',
			'<sup>{TEXT}</sup>'
		],
		[
			'[small]{TEXT}[/small]',
			'<small>{TEXT}</small>'
		],
		[
			'[table]{TEXT}[/table]',
			'<table>{TEXT}</table>'
		],
		[
			'[tr]{TEXT}[/tr]',
			'<tr>{TEXT}</tr>'
		],
		[
			'[td]{TEXT}[/td]',
			'<td>{TEXT}</td>'
		],
		[
			'[spoiler title={TEXT}]{TEXT}[/spoiler]',
			'<details class="label label-primary"><summary><xsl:choose>
				<xsl:when test="@title"><xsl:value-of select="@title"/></xsl:when>
				<xsl:otherwise>Spoiler</xsl:otherwise>
			</xsl:choose></summary><pre><code><div style="text-align:left;"><xsl:apply-templates/></div></code></pre></details>'
		],
	];

	/**
	* {@inheritdoc}
	*/
	public function configure(Configurator $configurator)
	{
		$configurator->rootRules->enableAutoLineBreaks();

		$configurator->Autoemail;
		$configurator->Autolink;
		$configurator->MediaEmbed->add('youtube');
	}
}