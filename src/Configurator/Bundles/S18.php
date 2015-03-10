<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Bundles;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Bundle;

class S18 extends Bundle
{
	public function configure(Configurator $configurator)
	{
		$configurator->rootRules->enableAutoLineBreaks();

		$configurator->urlConfig->allowScheme('ftp');
		$configurator->urlConfig->allowScheme('ftps');
		$configurator->rendering->parameters['L_CODE']        = 'Code';
		$configurator->rendering->parameters['L_CODE_SELECT'] = '[Select]';
		$configurator->rendering->parameters['L_QUOTE']       = 'Quote';
		$configurator->rendering->parameters['L_QUOTE_FROM']  = 'Quote from';
		$configurator->rendering->parameters['L_SEARCH_ON']   = 'on';

		$prependFtp  = 's9e\\TextFormatter\\Bundles\\S18\\Helper::prependFtp';
		$prependHttp = 's9e\\TextFormatter\\Bundles\\S18\\Helper::prependHttp';
		$configurator->BBCodes->bbcodeMonkey->allowedFilters[] = $prependFtp;
		$configurator->BBCodes->bbcodeMonkey->allowedFilters[] = $prependHttp;

		$configurator->attributeFilters
			->add('#iurl', 's9e\\TextFormatter\\Bundles\\S18\\Helper::filterIurl')
			->addParameterByName('urlConfig')
			->addParameterByName('logger')
			->markAsSafeAsURL();

		$smileys = array(
			array(':)',   'smiley.gif',      'Smiley'     ),
			array(';)',   'wink.gif',        'Wink'       ),
			array(':D',   'cheesy.gif',      'Cheesy'     ),
			array(';D',   'grin.gif',        'Grin'       ),
			array('>:[',  'angry.gif',       'Angry'      ),
			array(':[',   'sad.gif',         'Sad'        ),
			array(':o',   'shocked.gif',     'Shocked'    ),
			array('8)',   'cool.gif',        'Cool'       ),
			array('???',  'huh.gif',         'Huh?'       ),
			array('::)',  'rolleyes.gif',    'Roll Eyes'  ),
			array(':P',   'tongue.gif',      'Tongue'     ),
			array(':-[',  'embarrassed.gif', 'Embarrassed'),
			array(':-X',  'lipsrsealed.gif', 'Lips Sealed'),
			array(':-\\', 'undecided.gif',   'Undecided'  ),
			array(':-*',  'kiss.gif',        'Kiss'       ),
			array(":'[",  'cry.gif',         'Cry'        ),
			array('>:D',  'evil.gif',        'Evil'       ),
			array('^-^',  'azn.gif',         'Azn'        ),
			array('O0',   'afro.gif',        'Afro'       ),
			array(':))',  'laugh.gif',       'Laugh'      ),
			array('C:-)', 'police.gif',      'Police'     ),
			array('O:-)', 'angel.gif',       'Angel'      )
		);
		foreach ($smileys as $_8ba0cb0a)
		{
			list($code, $filename, $title) = $_8ba0cb0a;
			$configurator->Emoticons->add(
				$code,
				'<img src="{$SMILEYS_PATH}' . $filename . '" alt="' . $code . '" title="' . $title . '" class="smiley"/>'
			);
		}

		$bbcodes = array(
			array(
				'[abbr={TEXT1}]{TEXT2}[/abbr]',
				'<abbr title="{TEXT1}">{TEXT2}</abbr>'
			),
			array(
				'[acronym={TEXT1}]{TEXT2}[/acronym]',
				'<acronym title="{TEXT1}">{TEXT2}</acronym>'
			),
			array(
				'[anchor={REGEXP=/^#?[a-z][-a-z_0-9]*$/i}]{TEXT}[/anchor]',
				'<span id="post_{@anchor}">{TEXT}</span>'
			),
			array(
				'[b]{TEXT}[/b]',
				'<span class="bbc_bold">{TEXT}</span'
			),
			array(
				'[bdo={CHOICE=ltr,rtl}]{TEXT}[/bdo]',
				'<bdo dir="{CHOICE}">{TEXT}</bdo>'
			),
			array(
				'[black]{TEXT}[/black]',
				'<span style="color: black;" class="bbc_color">{TEXT}</span>'
			),
			array(
				'[blue]{TEXT}[/blue]',
				'<span style="color: blue;" class="bbc_color">{TEXT}</span>'
			),
			array(
				'[br]',
				'<br/>'
			),
			array(
				'[center]{TEXT}[/center]',
				'<div align="center">{TEXT}</div>'
			),
			array(
				'[code lang={SIMPLETEXT;optional}]{TEXT}[/code]',
				'<div class="codeheader">{L_CODE}:<xsl:if test="@lang"> ({SIMPLETEXT})</xsl:if> <a href="#" onclick="return smfSelectText(this);" class="codeoperation">{L_CODE_SELECT}</a></div>
				<xsl:choose>
					<xsl:when test="$IS_GECKO or $IS_OPERA">
						<pre style="margin: 0; padding: 0;">
							<code class="bbc_code">{TEXT}</code>
						</pre>
					</xsl:when>
					<xsl:otherwise>
						<code class="bbc_code">{TEXT}</code>
					</xsl:otherwise>
				</xsl:choose>'
			),
			array(
				'[color={COLOR}]{TEXT}[/color]',
				'<span style="color: {COLOR};" class="bbc_color">{TEXT}</span>'
			),
			array(
				'[email={EMAIL;useContent}]{TEXT}[/email]',
				'<a href="mailto:{EMAIL}" class="bbc_email">{TEXT}</a>'
			),
			array(
				'[flash={NUMBER1},{NUMBER2}]{URL;preFilter=' . $prependHttp . '}[/flash]',
				'<xsl:choose>
					<xsl:when test="$IS_IE">
						<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="{NUMBER1}" height="{NUMBER2}"><param name="movie" value="{URL}" /><param name="play" value="true" /><param name="loop" value="true" /><param name="quality" value="high" /><param name="AllowScriptAccess" value="never" /><embed src="{URL}" width="{NUMBER1}" height="{NUMBER2}" play="true" loop="true" quality="high" AllowScriptAccess="never" /><noembed><a href="{URL}" target="_blank" class="new_win">{URL}</a></noembed></object>
					</xsl:when>
					<xsl:otherwise>
						<embed type="application/x-shockwave-flash" src="{URL}" width="{NUMBER1}" height="{NUMBER2}" play="true" loop="true" quality="high" AllowScriptAccess="never" /><noembed><a href="{URL}" target="_blank" class="new_win">{URL}</a></noembed>
					</xsl:otherwise>
				</xsl:choose>'
			),
			array(
				'[font={SIMPLETEXT}]{TEXT}[/font]',
				'<span style="font-family: {SIMPLETEXT};" class="bbc_font">{TEXT}</span>'
			),
			array(
				'[ftp={URL;useContent;preFilter=' . $prependFtp . '}]{TEXT}[/ftp]',
				'<a href="{URL}" class="bbc_ftp new_win" target="_blank">{TEXT}</a>'
			),
			array(
				'[glow={COLOR},{NUMBER}]{TEXT}[/glow]',
				'<xsl:choose>
					<xsl:when test="$IS_IE">
						<table border="0" cellpadding="0" cellspacing="0" style="display: inline; vertical-align: middle; font: inherit;"><tr><td style="filter: Glow(color={COLOR}, strength={NUMBER}); font: inherit;">{TEXT}</td></tr></table>
					</xsl:when>
					<xsl:otherwise>
						<span style="text-shadow: {COLOR} 1px 1px 1px">{TEXT}</span>
					</xsl:otherwise>
				</xsl:choose>'
			),
			array(
				'[green]{TEXT}[/green]',
				'<span style="color: green;" class="bbc_color">{TEXT}</span>'
			),
			array(
				'[hr]',
				'<hr/>'
			),
			array(
				'[html]{TEXT}[/html]',
				'{TEXT}'
			),
			array(
				'[i]{TEXT}[/i]',
				'<em>{TEXT}</em>'
			),
			array(
				'[img alt={TEXT;optional} height={NUMBER;optional} src={URL;useContent} width={NUMBER;optional}]',
				'<img src="{URL}">
					<xsl:copy-of select="@alt"/>
					<xsl:copy-of select="@height"/>
					<xsl:copy-of select="@width"/>
					<xsl:attribute name="class">bbc_img<xsl:if test="@height or @width"> resized</xsl:if></xsl:attribute>
				</img>'
			),
			array(
				'[iurl={IURL;useContent}]{TEXT}[/iurl]',
				'<a href="{IURL}" class="bbc_link">{TEXT}</a>'
			),
			array(
				'[left]{TEXT}[/left]',
				'<div style="text-align: left;">{TEXT}</div>'
			),
			array(
				'[li]{TEXT}[/li]',
				'<li>{TEXT}</li>'
			),
			array(
				'[list type={CHOICE=none,disc,circle,square,decimal,decimal-leading-zero,lower-roman,upper-roman,lower-alpha,upper-alpha,lower-greek,lower-latin,upper-latin,hebrew,armenian,georgian,cjk-ideographic,hiragana,katakana,hiragana-iroha,katakana-iroha;optional}]{TEXT}[/list]',
				'<xsl:choose>
					<xsl:when test="@type">
						<ul class="bbc_list" style="list-style-type: {CHOICE};">{TEXT}</ul>
					</xsl:when>
					<xsl:otherwise>
						<ul class="bbc_list">{TEXT}</ul>
					</xsl:otherwise>
				</xsl:choose>'
			),
			array(
				'[ltr]{TEXT}[/ltr]',
				'<div dir="ltr">{TEXT}</div>'
			),
			array(
				'[me={TEXT1} #denyChild=me]{TEXT2}[/me]',
				'<div class="meaction">* {TEXT1} {TEXT2}</div>'
			),
			array(
				'[move]{TEXT}[/move]',
				'<marquee>{TEXT}</marquee>'
			),
			array(
				'[nobbc #ignoreTags=true]{TEXT}[/nobbc]',
				'{TEXT}'
			),
			array(
				'[pre]{TEXT}[/pre]',
				'<pre>{TEXT}</pre>'
			),
			array(
				'[quote author={TEXT1;optional} date={NUMBER;optional} link={REGEXP=!^(?:board=\\d+;)?(?:t(?:opic|hreadid)=[\\dmsg#./]{1,40}(?:;start=[\\dmsg#./]{1,40})?|msg=\\d+?|action=profile;u=\\d+)$!;optional}]{TEXT}[/quote]',
				'<div class="quoteheader">
					<div class="topslice_quote">
						<xsl:choose>
							<xsl:when test="not(@author)">{L_QUOTE}</xsl:when>
							<xsl:when test="@date and @link">
								<a href="{SCRIPT_URL}?{@link}">{L_QUOTE_FROM}: {@author} {L_SEARCH_ON} {@date}</a>
							</xsl:when>
							<xsl:otherwise>{L_QUOTE_FROM}: {@author}</xsl:otherwise>
						</xsl:choose>
					</div>
				</div>
				<blockquote>{TEXT}</blockquote>
				<div class="quotefooter">
					<div class="botslice_quote"></div>
				</div>'
			),
			array(
				'[red]{TEXT}[/red]',
				'<span style="color: red;" class="bbc_color">{TEXT}</span>'
			),
			array(
				'[right]{TEXT}[/right]',
				'<div style="text-align: right;">{TEXT}</div>'
			),
			array(
				'[rtl]{TEXT}[/rtl]',
				'<div dir="rtl">{TEXT}</div>'
			),
			array(
				'[s]{TEXT}[/s]',
				'<del>{TEXT}</del>'
			),
			array(
				'[shadow={PARSE=/^(?<color>[#0-9a-zA-Z\-]{3,12}),(?<direction>left|right|top|bottom|[0123]\\d{0,2})/}]{TEXT}[/shadow]',
				'<span>
					<xsl:attribute name="style">
						<xsl:choose>
							<xsl:when test="$IS_IE">
								<xsl:text>display: inline-block; filter: Shadow(color=</xsl:text>
								<xsl:value-of select="@color"/>
								<xsl:text>, direction=</xsl:text>
								<xsl:choose>
									<xsl:when test="@direction=\'left\'">270</xsl:when>
									<xsl:when test="@direction=\'right\'">90</xsl:when>
									<xsl:when test="@direction=\'top\'">0</xsl:when>
									<xsl:when test="@direction=\'bottom\'">180</xsl:when>
									<xsl:otherwise>{@direction}</xsl:otherwise>
								</xsl:choose>
								<xsl:text>); height: 1.2em;</xsl:text>
							</xsl:when>
							<xsl:otherwise>
								<xsl:text>text-shadow: </xsl:text>
								<xsl:value-of select="@color"/>
								<xsl:text> </xsl:text>
								<xsl:choose>
									<xsl:when test="@direction=\'top\' or @direction&lt;50">0 -2px 1px</xsl:when>
									<xsl:when test="@direction=\'right\' or @direction&lt;100">2px 0 1px</xsl:when>
									<xsl:when test="@direction=\'bottom\' or @direction&lt;190">0 2px 1px</xsl:when>
									<xsl:when test="@direction=\'left\' or @direction&lt;280">-2px 0 1px</xsl:when>
									<xsl:otherwise>1px 1px 1px</xsl:otherwise>
								</xsl:choose>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:attribute>
					{TEXT}
				</span>'
			),
			array(
				'[size={REGEXP=/^([1-9][\\d]?p[xt]|small(?:er)?|larger?|xx?-(?:small|large)|medium|(?:0\\.[1-9]|[1-9](\\.\\d\\d?)?)?em)$/}]{TEXT}[/size]',
				'<span style="font-size: {@size};" class="bbc_size">{TEXT}</span>'
			),
			array(
				'[sub]{TEXT}[/sub]',
				'<sub>{TEXT}</sub>'
			),
			array(
				'[sup]{TEXT}[/sup]',
				'<sup>{TEXT}</sup>'
			),
			array(
				'[table]{TEXT}[/table]',
				'<table class="bbc_table">{TEXT}</table>'
			),
			array(
				'[td]{TEXT}[/td]',
				'<td>{TEXT}</td>'
			),
			array(
				'[time={NUMBER;useContent}]',
				'{@time}'
			),
			array(
				'[tr]{TEXT}[/tr]',
				'<tr>{TEXT}</tr>'
			),
			array(
				'[tt]{TEXT}[/tt]',
				'<span class="bbc_tt">{TEXT}</span>'
			),
			array(
				'[u]{TEXT}[/u]',
				'<span class="bbc_u">{TEXT}</span>'
			),
			array(
				'[url={URL;useContent;preFilter=' . $prependHttp . '}]{TEXT}[/url]',
				'<a href="{URL}" class="bbc_link" target="_blank">{TEXT}</a>'
			),
			array(
				'[white]{TEXT}[/white]',
				'<span style="color: white;" class="bbc_color">{TEXT}</span>'
			),
		);

		foreach ($bbcodes as $_41fa8b0)
		{
			list($definition, $template) = $_41fa8b0;
			$configurator->BBCodes->addCustom($definition, $template);
		}

		$bbcode = $configurator->BBCodes->add('php');
		$bbcode->tagName = 'CODE';
		$bbcode->predefinedAttributes['lang'] = 'php';

		$configurator->Autoemail;
		$configurator->Autolink;

		$configurator->HTMLElements->allowElement('a');
		$configurator->HTMLElements->allowAttribute('a', 'href')->required = \true;
		$configurator->HTMLElements->allowElement('b');
		$configurator->HTMLElements->allowElement('blockquote');
		$configurator->HTMLElements->allowElement('br');
		$configurator->HTMLElements->allowElement('del');
		$configurator->HTMLElements->allowElement('em');
		$configurator->HTMLElements->allowElement('hr');
		$configurator->HTMLElements->allowElement('i');
		$configurator->HTMLElements->allowElement('img');
		$configurator->HTMLElements->allowAttribute('img', 'alt');
		$configurator->HTMLElements->allowAttribute('img', 'height');
		$configurator->HTMLElements->allowAttribute('img', 'src')->required = \true;
		$configurator->HTMLElements->allowAttribute('img', 'width');
		$configurator->HTMLElements->allowElement('ins');
		$configurator->HTMLElements->allowElement('pre');
		$configurator->HTMLElements->allowElement('s');
		$configurator->HTMLElements->allowElement('u');

		$htmlTag = $configurator->tags['HTML'];
		$htmlTag->rules->defaultChildRule = 'deny';
		$htmlTag->rules->defaultDescendantRule = 'deny';

		foreach ($configurator->tags as $tagName => $tag)
			if (\substr($tagName, 0, 5) === 'html:')
			{
				$tag->rules->requireAncestor('html');
				$htmlTag->rules->allowDescendant($tagName);
			}
			else
				$htmlTag->rules->denyDescendant($tagName);
	}

	public static function getOptions()
	{
		return array(
			'beforeRender'  => 's9e\\TextFormatter\\Bundles\\S18\\Helper::applyTimeformat',
			'parserSetup'   => 's9e\\TextFormatter\\Bundles\\S18\\Helper::configureParser',
			'rendererSetup' => 's9e\\TextFormatter\\Bundles\\S18\\Helper::configureRenderer'
		);
	}
}