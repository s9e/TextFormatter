<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Bundles;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Bundle;

class F5 extends Bundle
{
	/**
	* {@inheritdoc}
	*/
	public function configure(Configurator $configurator)
	{
		$bbcodes = [
			[
				'[b]{TEXT}[/b]',
				'<strong>{TEXT}</strong>'
			],
			[
				'[i]{TEXT}[/i]',
				'<em>{TEXT}</em>'
			],
			[
				'[u]{TEXT}[/u]',
				'<span class="bbu">{TEXT}</span>'
			],
			[
				'[s]{TEXT}[/s]',
				'<span class="bbs">{TEXT}</span>'
			],
			[
				'[del]{TEXT}[/del]',
				'<del>{TEXT}</del>'
			],
			[
				'[ins]{TEXT}[/ins]',
				'<ins>{TEXT}</ins>'
			],
			[
				'[em]{TEXT}[/em]',
				'<em>{TEXT}</em>'
			],
			[
				'[color={COLOR}]{TEXT}[/color]',
				'<span style="color: {COLOR}">{TEXT}</span>'
			],
			[
				'[h]{TEXT}[/h]',
				'<h5>{TEXT}</h5>'
			],
			[
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
			],
			[
				'[email={EMAIL;useContent}]{TEXT}[/email]',
				'<a href="mailto:{EMAIL}">{TEXT}</a>'
			],
			[
				'[topic id={UINT;useContent}]{TEXT}[/topic]',
				'<a href="{$BASE_URL}viewtopic.php?id={UINT}">
					<xsl:choose>
						<xsl:when test="text()=@id"><xsl:value-of select="$BASE_URL"/>viewtopic.php?id={UINT}</xsl:when>
						<xsl:otherwise><xsl:apply-templates/></xsl:otherwise>
					</xsl:choose>
				</a>'
			],
			[
				'[post id={UINT;useContent}]{TEXT}[/post]',
				'<a href="{$BASE_URL}viewtopic.php?pid={UINT}#{UINT}">
					<xsl:choose>
						<xsl:when test="text()=@id"><xsl:value-of select="$BASE_URL"/>viewtopic.php?pid={UINT}#{UINT}</xsl:when>
						<xsl:otherwise><xsl:apply-templates/></xsl:otherwise>
					</xsl:choose>
				</a>'
			],
			[
				'[forum id={UINT;useContent}]{TEXT}[/forum]',
				'<a href="{$BASE_URL}viewforum.php?id={UINT}">
					<xsl:choose>
						<xsl:when test="text()=@id"><xsl:value-of select="$BASE_URL"/>viewforum.php?id={UINT}</xsl:when>
						<xsl:otherwise><xsl:apply-templates/></xsl:otherwise>
					</xsl:choose>
				</a>'
			],
			[
				'[user id={UINT;useContent}]{TEXT}[/user]',
				'<a href="{$BASE_URL}profile.php?id={UINT}">
					<xsl:choose>
						<xsl:when test="text()=@id"><xsl:value-of select="$BASE_URL"/>profile.php?id={UINT}</xsl:when>
						<xsl:otherwise><xsl:apply-templates/></xsl:otherwise>
					</xsl:choose>
				</a>'
			],
			[
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
			],
			[
				'[quote author={TEXT1;optional}]{TEXT2}[/quote]',
				'<div class="quotebox">
					<xsl:if test="@author"><cite>{TEXT1} {L_WROTE}</cite></xsl:if>
					<blockquote><div>{TEXT2}</div></blockquote>
				</div>'
			],
			[
				'[code]{TEXT}[/code]',
				'<div class="codebox">
					<pre>
						<xsl:if test="string-length(.) - string-length(translate(., \'&#10;\', \'\')) &gt; 28">
							<xsl:attribute name="class">vscroll</xsl:attribute>
						</xsl:if>
						<code>{TEXT}</code>
					</pre>
				</div>'
			],
			[
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
			],
			[
				'[* $tagName=LI]{TEXT}[/*]',
				'<li>{TEXT}</li>'
			],
		];

		// Alias COLOUR to COLOR
		$configurator->BBCodes->add('COLOUR')->tagName = 'COLOR';

		// Add the default BBCodes
		foreach ($bbcodes as list($definition, $template))
		{
			$configurator->BBCodes->addCustom($definition, $template);
		}

		// Add some default limits
		$configurator->tags['QUOTE']->nestingLimit = 3;
		$configurator->tags['LIST']->nestingLimit  = 5;

		$emoticons = [
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
		];

		$configurator->Emoticons->notAfter  = '\\S';
		$configurator->Emoticons->notBefore = '\\pL\\pN';

		foreach ($emoticons as $code => $filename)
		{
			$configurator->Emoticons->add(
				$code,
				'<img src="{$BASE_URL}img/smilies/' . $filename . '.png" width="15" height="15" alt="' . $filename . '"/>'
			);
		}

		$configurator->Autoemail;
		$configurator->Autolink;
		$configurator->urlConfig->allowScheme('ftp');
		$configurator->rootRules->enableAutoLineBreaks();
		$configurator->rulesGenerator->append('ManageParagraphs');
		$configurator->rendering->type = 'xhtml';
		$configurator->rendering->parameters['SHOW_IMG'] = 1;
		$configurator->rendering->parameters['L_WROTE'] = 'wrote:';
	}
}