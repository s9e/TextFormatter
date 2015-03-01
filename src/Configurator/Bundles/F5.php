<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Bundles;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Bundle;

class F5 extends Bundle
{
	public function configure(Configurator $configurator)
	{
		$bbcodes = array(
			array(
				'[b]{TEXT}[/b]',
				'<strong>{TEXT}</strong>'
			),
			array(
				'[i]{TEXT}[/i]',
				'<em>{TEXT}</em>'
			),
			array(
				'[u]{TEXT}[/u]',
				'<span class="bbu">{TEXT}</span>'
			),
			array(
				'[s]{TEXT}[/s]',
				'<span class="bbs">{TEXT}</span>'
			),
			array(
				'[del]{TEXT}[/del]',
				'<del>{TEXT}</del>'
			),
			array(
				'[ins]{TEXT}[/ins]',
				'<ins>{TEXT}</ins>'
			),
			array(
				'[em]{TEXT}[/em]',
				'<em>{TEXT}</em>'
			),
			array(
				'[color={COLOR}]{TEXT}[/color]',
				'<span style="color: {COLOR}">{TEXT}</span>'
			),
			array(
				'[h]{TEXT}[/h]',
				'<h5>{TEXT}</h5>'
			),
			array(
				'[url={URL;useContent}]{TEXT}[/url]',
				'<a href="{URL}" rel="nofollow">
					<xsl:choose>
						<xsl:when test="text() = @url and . = @url and 55 &lt; string-length(.) - string-length(st) - string-length(et)">
							<xsl:value-of select="substring(., 1, 39)"/>
							<xsl:text> … </xsl:text>
							<xsl:value-of select="substring(., string-length(.) - 10)"/>
						</xsl:when>
						<xsl:otherwise>{TEXT}</xsl:otherwise>
					</xsl:choose>
				</a>'
			),
			array(
				'[email={EMAIL;useContent}]{TEXT}[/email]',
				'<a href="mailto:{EMAIL}">{TEXT}</a>'
			),
			array(
				'[topic id={UINT;useContent}]{TEXT}[/topic]',
				'<a href="{$BASE_URL}viewtopic.php?id={UINT}">
					<xsl:choose>
						<xsl:when test="text()=@id"><xsl:value-of select="$BASE_URL"/>viewtopic.php?id={UINT}</xsl:when>
						<xsl:otherwise><xsl:apply-templates/></xsl:otherwise>
					</xsl:choose>
				</a>'
			),
			array(
				'[post id={UINT;useContent}]{TEXT}[/post]',
				'<a href="{$BASE_URL}viewtopic.php?pid={UINT}#{UINT}">
					<xsl:choose>
						<xsl:when test="text()=@id"><xsl:value-of select="$BASE_URL"/>viewtopic.php?pid={UINT}#{UINT}</xsl:when>
						<xsl:otherwise><xsl:apply-templates/></xsl:otherwise>
					</xsl:choose>
				</a>'
			),
			array(
				'[forum id={UINT;useContent}]{TEXT}[/forum]',
				'<a href="{$BASE_URL}viewforum.php?id={UINT}">
					<xsl:choose>
						<xsl:when test="text()=@id"><xsl:value-of select="$BASE_URL"/>viewforum.php?id={UINT}</xsl:when>
						<xsl:otherwise><xsl:apply-templates/></xsl:otherwise>
					</xsl:choose>
				</a>'
			),
			array(
				'[user id={UINT;useContent}]{TEXT}[/user]',
				'<a href="{$BASE_URL}profile.php?id={UINT}">
					<xsl:choose>
						<xsl:when test="text()=@id"><xsl:value-of select="$BASE_URL"/>profile.php?id={UINT}</xsl:when>
						<xsl:otherwise><xsl:apply-templates/></xsl:otherwise>
					</xsl:choose>
				</a>'
			),
			array(
				'[img alt={TEXT;optional}]{URL}[/img]',
				'<xsl:choose>
					<xsl:when test="$IS_SIGNATURE and $SHOW_IMG_SIG">
						<img class="sigimage" src="{URL}" alt="{TEXT}"/>
					</xsl:when>
					<xsl:when test="not($IS_SIGNATURE) and $SHOW_IMG = 1">
						<span class="postimg"><img src="{URL}" alt="{TEXT}"/></span>
					</xsl:when>
					<xsl:otherwise>
						<xsl:apply-templates/>
					</xsl:otherwise>
				</xsl:choose>'
			),
			array(
				'[quote author={TEXT1;optional}]{TEXT2}[/quote]',
				'<div class="quotebox">
					<xsl:if test="@author"><cite>{TEXT1} {L_WROTE}</cite></xsl:if>
					<blockquote><div>{TEXT2}</div></blockquote>
				</div>'
			),
			array(
				'[code]{TEXT}[/code]',
				'<div class="codebox">
					<pre>
						<xsl:if test="string-length(.) - string-length(translate(., \'&#10;\', \'\')) &gt; 28">
							<xsl:attribute name="class">vscroll</xsl:attribute>
						</xsl:if>
						<code>{TEXT}</code>
					</pre>
				</div>'
			),
			array(
				'[list type={CHOICE=1,a;optional}]{ANYTHING}[/list]',
				'<xsl:choose>
					<xsl:when test="@type=\'1\'">
						<ol class="decimal"><xsl:apply-templates/></ol>
					</xsl:when>
					<xsl:when test="@type=\'a\'">
						<ol class="alpha"><xsl:apply-templates/></ol>
					</xsl:when>
					<xsl:otherwise>
						<ul><xsl:apply-templates/></ul>
					</xsl:otherwise>
				</xsl:choose>'
			),
			array(
				'[* $tagName=LI]{TEXT}[/*]',
				'<li>{TEXT}</li>'
			),
		);

		$configurator->BBCodes->add('COLOUR')->tagName = 'COLOR';

		foreach ($bbcodes as $_69183664)
		{
			list($definition, $template) = $_69183664;
			$configurator->BBCodes->addCustom($definition, $template);
		}

		$configurator->tags['QUOTE']->nestingLimit = 3;
		$configurator->tags['LIST']->nestingLimit  = 5;

		$emoticons = array(
			':)' => 'smile',
			'=)' => 'smile',
			':|' => 'neutral',
			'=|' => 'neutral',
			':(' => 'sad',
			'=(' => 'sad',
			':D' => 'big_smile',
			'=D' => 'big_smile',
			':o' => 'yikes',
			':O' => 'yikes',
			';)' => 'wink',
			':/' => 'hmm',
			':P' => 'tongue',
			':p' => 'tongue',
			':lol:' => 'lol',
			':mad:' => 'mad',
			':rolleyes:' => 'roll',
			':cool:' => 'cool'
		);

		$configurator->Emoticons->notAfter  = '\\S';
		$configurator->Emoticons->notBefore = '\\pL\\pN';

		foreach ($emoticons as $code => $filename)
			$configurator->Emoticons->add(
				$code,
				'<img src="{$BASE_URL}img/smilies/' . $filename . '.png" width="15" height="15" alt="' . $filename . '"/>'
			);

		$configurator->Autoemail;
		$configurator->Autolink;
		$configurator->urlConfig->allowScheme('ftp');
		$configurator->rootRules->enableAutoLineBreaks();
		$configurator->rulesGenerator->append('ManageParagraphs');
		$configurator->rendering->parameters['SHOW_IMG'] = 1;
		$configurator->rendering->parameters['L_WROTE'] = 'wrote:';
	}
}