<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Bundles;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Bundle;

class S18 extends Bundle
{
	/**
	* {@inheritdoc}
	*/
	public function configure(Configurator $configurator)
	{
		$configurator->urlConfig->allowScheme('ftp');
		$configurator->urlConfig->allowScheme('ftps');
		$configurator->stylesheet->parameters['L_CODE']        = 'Code';
		$configurator->stylesheet->parameters['L_CODE_SELECT'] = '[Select]';
		$configurator->stylesheet->parameters['L_QUOTE']       = 'Quote';
		$configurator->stylesheet->parameters['L_QUOTE_FROM']  = 'Quote from';
		$configurator->stylesheet->parameters['L_SEARCH_ON']   = 'on';

		// Indicate that SCRIPT_URL and SMILEYS_PATH are safe to be used as a URL
		$configurator->stylesheet->parameters->add('SCRIPT_URL')->markAsSafeAsURL();
		$configurator->stylesheet->parameters->add('SMILEYS_PATH')->markAsSafeAsURL();

		// Allow the methods that prepend http:// or ftp:// to an URL to be used on attributes
		$prependFtp  = 's9e\\TextFormatter\\Bundles\\S18\\Helper::prependFtp';
		$prependHttp = 's9e\\TextFormatter\\Bundles\\S18\\Helper::prependHttp';
		$configurator->BBCodes->bbcodeMonkey->allowedFilters[] = $prependFtp;
		$configurator->BBCodes->bbcodeMonkey->allowedFilters[] = $prependHttp;

		// Register the filter for {IURL} tokens
		$configurator->attributeFilters
			->add('#iurl', 's9e\\TextFormatter\\Bundles\\S18\\Helper::filterIurl')
			->addParameterByName('urlConfig')
			->addParameterByName('logger')
			->markAsSafeAsURL();

		// Add the default smileys
		$smileys = [
			[':)',   'smiley.gif',      'Smiley'     ],
			[';)',   'wink.gif',        'Wink'       ],
			[':D',   'cheesy.gif',      'Cheesy'     ],
			[';D',   'grin.gif',        'Grin'       ],
			['>:[',  'angry.gif',       'Angry'      ],
			[':[',   'sad.gif',         'Sad'        ],
			[':o',   'shocked.gif',     'Shocked'    ],
			['8)',   'cool.gif',        'Cool'       ],
			['???',  'huh.gif',         'Huh?'       ],
			['::)',  'rolleyes.gif',    'Roll Eyes'  ],
			[':P',   'tongue.gif',      'Tongue'     ],
			[':-[',  'embarrassed.gif', 'Embarrassed'],
			[':-X',  'lipsrsealed.gif', 'Lips Sealed'],
			[':-\\', 'undecided.gif',   'Undecided'  ],
			[':-*',  'kiss.gif',        'Kiss'       ],
			[":'[",  'cry.gif',         'Cry'        ],
			['>:D',  'evil.gif',        'Evil'       ],
			['^-^',  'azn.gif',         'Azn'        ],
			['O0',   'afro.gif',        'Afro'       ],
			[':))',  'laugh.gif',       'Laugh'      ],
			['C:-)', 'police.gif',      'Police'     ],
			['O:-)', 'angel.gif',       'Angel'      ]
		];
		foreach ($smileys as list($code, $filename, $title))
		{
			$configurator->Emoticons->add(
				$code,
				'<img src="{$SMILEYS_PATH}' . $filename . '" alt="' . $code . '" title="' . $title . '" class="smiley"/>'
			);
		}

		// Default BBCodes
		$bbcodes = [
			[
				'[abbr={TEXT1}]{TEXT2}[/abbr]',
				'<abbr title="{TEXT1}">{TEXT2}</abbr>'
			],
			[
				'[acronym={TEXT1}]{TEXT2}[/acronym]',
				'<acronym title="{TEXT1}">{TEXT2}</acronym>'
			],
			[
				'[anchor={REGEXP=/^#?[a-z][-a-z_0-9]*$/i}]{TEXT}[/anchor]',
				'<span id="post_{@anchor}">{TEXT}</span>'
			],
			[
				'[b]{TEXT}[/b]',
				'<span class="bbc_bold">{TEXT}</span'
			],
			[
				'[bdo={CHOICE=ltr,rtl}]{TEXT}[/bdo]',
				'<bdo dir="{CHOICE}">{TEXT}</bdo>'
			],
			[
				'[black]{TEXT}[/black]',
				'<span style="color: black;" class="bbc_color">{TEXT}</span>'
			],
			[
				'[blue]{TEXT}[/blue]',
				'<span style="color: blue;" class="bbc_color">{TEXT}</span>'
			],
			[
				'[br]',
				'<br/>'
			],
			[
				'[center]{TEXT}[/center]',
				'<div align="center">{TEXT}</div>'
			],
			[
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
			],
			[
				'[color={COLOR}]{TEXT}[/color]',
				'<span style="color: {COLOR};" class="bbc_color">{TEXT}</span>'
			],
			[
				'[email={EMAIL;useContent}]{TEXT}[/email]',
				'<a href="mailto:{EMAIL}" class="bbc_email">{TEXT}</a>'
			],
			[
				'[flash={NUMBER1},{NUMBER2}]{URL;preFilter=' . $prependHttp . '}[/flash]',
				'<xsl:choose>
					<xsl:when test="$IS_IE">
						<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="{NUMBER1}" height="{NUMBER2}"><param name="movie" value="{URL}" /><param name="play" value="true" /><param name="loop" value="true" /><param name="quality" value="high" /><param name="AllowScriptAccess" value="never" /><embed src="{URL}" width="{NUMBER1}" height="{NUMBER2}" play="true" loop="true" quality="high" AllowScriptAccess="never" /><noembed><a href="{URL}" target="_blank" class="new_win">{URL}</a></noembed></object>
					</xsl:when>
					<xsl:otherwise>
						<embed type="application/x-shockwave-flash" src="{URL}" width="{NUMBER1}" height="{NUMBER2}" play="true" loop="true" quality="high" AllowScriptAccess="never" /><noembed><a href="{URL}" target="_blank" class="new_win">{URL}</a></noembed>
					</xsl:otherwise>
				</xsl:choose>'
			],
			[
				'[font={SIMPLETEXT}]{TEXT}[/font]',
				'<span style="font-family: {SIMPLETEXT};" class="bbc_font">{TEXT}</span>'
			],
			[
				'[ftp={URL;useContent;preFilter=' . $prependFtp . '}]{TEXT}[/ftp]',
				'<a href="{URL}" class="bbc_ftp new_win" target="_blank">{TEXT}</a>'
			],
			[
				'[glow={COLOR},{NUMBER}]{TEXT}[/glow]',
				'<xsl:choose>
					<xsl:when test="$IS_IE">
						<table border="0" cellpadding="0" cellspacing="0" style="display: inline; vertical-align: middle; font: inherit;"><tr><td style="filter: Glow(color={COLOR}, strength={NUMBER}); font: inherit;">{TEXT}</td></tr></table>
					</xsl:when>
					<xsl:otherwise>
						<span style="text-shadow: {COLOR} 1px 1px 1px">{TEXT}</span>
					</xsl:otherwise>
				</xsl:choose>'
			],
			[
				'[green]{TEXT}[/green]',
				'<span style="color: green;" class="bbc_color">{TEXT}</span>'
			],
			[
				'[hr]',
				'<hr/>'
			],
			[
				'[html]{TEXT}[/html]',
				'{TEXT}'
			],
			[
				'[i]{TEXT}[/i]',
				'<em>{TEXT}</em>'
			],
			[
				'[img alt={TEXT;optional} height={NUMBER;optional} width={NUMBER;optional}]{URL}[/img]',
				'<img src="{URL}">
					<xsl:copy-of select="@alt"/>
					<xsl:copy-of select="@height"/>
					<xsl:copy-of select="@width"/>
					<xsl:attribute name="class">bbc_img<xsl:if test="@height or @width"> resized</xsl:if></xsl:attribute>
				</img>'
			],
			[
				'[iurl={IURL;useContent}]{TEXT}[/iurl]',
				'<a href="{IURL}" class="bbc_link">{TEXT}</a>'
			],
			[
				'[left]{TEXT}[/left]',
				'<div style="text-align: left;">{TEXT}</div>'
			],
			[
				'[li]{TEXT}[/li]',
				'<li>{TEXT}</li>'
			],
			[
				'[list type={CHOICE=none,disc,circle,square,decimal,decimal-leading-zero,lower-roman,upper-roman,lower-alpha,upper-alpha,lower-greek,lower-latin,upper-latin,hebrew,armenian,georgian,cjk-ideographic,hiragana,katakana,hiragana-iroha,katakana-iroha;optional}]{TEXT}[/list]',
				'<xsl:choose>
					<xsl:when test="@type">
						<ul class="bbc_list" style="list-style-type: {CHOICE};">{TEXT}</ul>
					</xsl:when>
					<xsl:otherwise>
						<ul class="bbc_list">{TEXT}</ul>
					</xsl:otherwise>
				</xsl:choose>'
			],
			[
				'[ltr]{TEXT}[/ltr]',
				'<div dir="ltr">{TEXT}</div>'
			],
			[
				'[me={TEXT1} #denyChild=me]{TEXT2}[/me]',
				'<div class="meaction">* {TEXT1} {TEXT2}</div>'
			],
			[
				'[move]{TEXT}[/move]',
				'<marquee>{TEXT}</marquee>'
			],
			[
				'[nobbc #ignoreTags=true]{TEXT}[/nobbc]',
				'{TEXT}'
			],
			[
				'[pre]{TEXT}[/pre]',
				'<pre>{TEXT}</pre>'
			],
			[
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
			],
			[
				'[red]{TEXT}[/red]',
				'<span style="color: red;" class="bbc_color">{TEXT}</span>'
			],
			[
				'[right]{TEXT}[/right]',
				'<div style="text-align: right;">{TEXT}</div>'
			],
			[
				'[rtl]{TEXT}[/rtl]',
				'<div dir="rtl">{TEXT}</div>'
			],
			[
				'[s]{TEXT}[/s]',
				'<del>{TEXT}</del>'
			],
			[
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
			],
			[
				'[size={REGEXP=/^([1-9][\\d]?p[xt]|small(?:er)?|larger?|xx?-(?:small|large)|medium|(?:0\\.[1-9]|[1-9](\\.\\d\\d?)?)?em)$/}]{TEXT}[/size]',
				'<span style="font-size: {@size};" class="bbc_size">{TEXT}</span>'
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
				'[table]{TEXT}[/table]',
				'<table class="bbc_table">{TEXT}</table>'
			],
			[
				'[td]{TEXT}[/td]',
				'<td>{TEXT}</td>'
			],
			[
				'[time={NUMBER;useContent}]',
				'{@time}'
			],
			[
				'[tr]{TEXT}[/tr]',
				'<tr>{TEXT}</tr>'
			],
			[
				'[tt]{TEXT}[/tt]',
				'<span class="bbc_tt">{TEXT}</span>'
			],
			[
				'[u]{TEXT}[/u]',
				'<span class="bbc_u">{TEXT}</span>'
			],
			[
				'[url={URL;useContent;preFilter=' . $prependHttp . '}]{TEXT}[/url]',
				'<a href="{URL}" class="bbc_link" target="_blank">{TEXT}</a>'
			],
			[
				'[white]{TEXT}[/white]',
				'<span style="color: white;" class="bbc_color">{TEXT}</span>'
			],
		];

		// Add the default BBCodes
		foreach ($bbcodes as list($definition, $template))
		{
			$configurator->BBCodes->addCustom($definition, $template);
		}

		// Create [php] as an alias for [code=php]
		$bbcode = $configurator->BBCodes->add('php');
		$bbcode->tagName = 'CODE';
		$bbcode->predefinedAttributes['lang'] = 'php';

		// Load the plugins after BBCodes so that they use the [url] and [email] BBCodes
		$configurator->Autoemail;
		$configurator->Autolink;

		// Allow some HTML
		$configurator->HTMLElements->allowElement('a');
		$configurator->HTMLElements->aliasElement('a', 'URL');
		$configurator->HTMLElements->allowAttribute('a', 'href');
		$configurator->HTMLElements->aliasAttribute('a', 'href', 'url');
		$configurator->HTMLElements->allowElement('b');
		$configurator->HTMLElements->allowElement('blockquote');
		$configurator->HTMLElements->allowElement('br');
		$configurator->HTMLElements->allowElement('del');
		$configurator->HTMLElements->allowElement('em');
		$configurator->HTMLElements->allowElement('hr');
		$configurator->HTMLElements->allowElement('i');
		$configurator->HTMLElements->allowElement('img');
		$configurator->HTMLElements->aliasElement('img', 'IMG');
		$configurator->HTMLElements->allowAttribute('img', 'alt');
		$configurator->HTMLElements->allowAttribute('img', 'height');
		$configurator->HTMLElements->allowAttribute('img', 'width');
		$configurator->HTMLElements->allowElement('ins');
		$configurator->HTMLElements->allowElement('pre');
		$configurator->HTMLElements->allowElement('s');
		$configurator->HTMLElements->allowElement('u');

		// Configure the HTML elements to require an [html] ancestor
		foreach ($configurator->tags as $tagName => $tag)
		{
			if (substr($tagName, 0, 5) === 'html:')
			{
				$tag->rules->requireAncestor('html');
			}
		}
	}
}