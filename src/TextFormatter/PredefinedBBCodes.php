<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter;

/**
* This class attempts to hold the definitions of the most commonly requested BBCodes.
* It is partially based on user requests found in forum software-oriented websites.
*
* @link http://www.phpbb.com/kb/article/adding-custom-bbcodes-in-phpbb3/
*/
class PredefinedBBCodes
{
	public function __construct(ConfigBuilder $cb)
	{
		$this->cb = $cb;
	}

	public function addB()
	{
		$this->cb->BBCodes->addBBCodeFromExample('[B]{TEXT}[/B]', '<strong>{TEXT}</strong>');
	}

	public function addI()
	{
		$this->cb->BBCodes->addBBCodeFromExample('[I]{TEXT}[/I]', '<em>{TEXT}</em>');
	}

	public function addU()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[U]{TEXT}[/U]',
			'<span style="text-decoration:underline">{TEXT}</span>'
		);
	}

	public function addS()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[S]{TEXT}[/S]',
			'<span style="text-decoration:line-through">{TEXT}</span>'
		);
	}

	/**
	* Polymorphic URL tag with optional support for the "title" attribute
	*
	* [URL]http://www.example.org[/URL]
	* [URL=http://www.example.org]example.org[/URL]
	* [URL title="The best site ever"]http://www.example.org[/URL]
	*/
	public function addURL()
	{
		$this->cb->BBCodes->addBBCode('URL', array(
			'defaultAttr' => 'url',
			'contentAttr' => 'url'
		));

		$this->cb->addTagAttribute('URL', 'url', 'url');
		$this->cb->addTagAttribute('URL', 'title', 'text', array('isRequired' => false));

		$this->cb->setTagTemplate(
			'URL',
			'<a href="{@url}">
				<xsl:if test="@title">
					<xsl:attribute name="title">
						<xsl:value-of select="@title" />
					</xsl:attribute>
				</xsl:if>
				<xsl:apply-templates/>
			</a>'
		);
	}

	/**
	* Polymorphic IMG tag with optional support for "title" and "alt"
	*
	* Note that no attempt is made to verify that the image's source is actually an image.
	*
	* [IMG]http://www.example.org/img.png[/IMG]
	*/
	public function addIMG()
	{
		$this->cb->BBCodes->addBBCode('IMG', array(
			'defaultAttr' => 'src',
			'contentAttr' => 'src',
			'autoClose'   => true,
			'defaultRule' => 'deny'
		));

		$this->cb->addTagAttribute('IMG', 'src', 'url');
		$this->cb->addTagAttribute('IMG', 'alt', 'text', array('isRequired' => false));
		$this->cb->addTagAttribute('IMG', 'title', 'text', array('isRequired' => false));

		$this->cb->setTagTemplate(
			'IMG',
			'<img src="{@src}">
				<xsl:if test="@alt">
					<xsl:attribute name="alt">
						<xsl:value-of select="@alt" />
					</xsl:attribute>
				</xsl:if>
				<xsl:if test="@title">
					<xsl:attribute name="title">
						<xsl:value-of select="@title" />
					</xsl:attribute>
				</xsl:if>
			</img>'
		);
	}

	public function addLIST()
	{
		$styles = array(
			'1',
			'01',
			'a',
			'i',
			/**
			* @see http://www.w3.org/TR/2002/WD-css3-lists-20021107/#list-content
			*/
			'normal', 'none',
			/**
			* @see http://www.w3.org/TR/2002/WD-css3-lists-20021107/#glyphs
			*/
			'box', 'check', 'circle', 'diamond', 'disc', 'hyphen', 'square',
			/**
			* @see http://www.w3.org/TR/2002/WD-css3-lists-20021107/#algorithmic
			*/
			'armenian', 'cjk-ideographic', 'ethiopic-numeric', 'georgian', 'hebrew', 'japanese-formal', 'japanese-informal', 'lower-armenian', 'lower-roman', 'simp-chinese-formal', 'simp-chinese-informal', 'syriac', 'tamil', 'trad-chinese-formal', 'trad-chinese-informal', 'upper-armenian', 'upper-roman',
			/**
			* @see http://www.w3.org/TR/2002/WD-css3-lists-20021107/#algorithmic
			*/
			'arabic-indic', 'binary', 'bengali', 'cambodian', 'decimal', 'decimal-leading-zero', 'devanagari', 'gujarati', 'gurmukhi', 'kannada', 'khmer', 'lao', 'lower-hexadecimal', 'malayalam', 'mongolian', 'myanmar', 'octal', 'oriya', 'persian', 'telugu', 'tibetan', 'thai', 'upper-hexadecimal', 'urdu',
			/**
			* @see http://www.w3.org/TR/2002/WD-css3-lists-20021107/#alphabetic
			*/
			'afar', 'amharic', 'amharic-abegede', 'cjk-earthly-branch', 'cjk-heavenly-stem', 'ethiopic', 'ethiopic-abegede', 'ethiopic-abegede-am-et', 'ethiopic-abegede-gez', 'ethiopic-abegede-ti-er', 'ethiopic-abegede-ti-et', 'ethiopic-halehame-aa-er', 'ethiopic-halehame-aa-et', 'ethiopic-halehame-am-et', 'ethiopic-halehame-gez', 'ethiopic-halehame-om-et', 'ethiopic-halehame-sid-et', 'ethiopic-halehame-so-et', 'ethiopic-halehame-ti-er', 'ethiopic-halehame-ti-et', 'ethiopic-halehame-tig', 'hangul', 'hangul-consonant', 'hiragana', 'hiragana-iroha', 'katakana', 'katakana-iroha', 'lower-alpha', 'lower-greek', 'lower-norwegian', 'lower-latin', 'oromo', 'sidama', 'somali', 'tigre', 'tigrinya-er', 'tigrinya-er-abegede', 'tigrinya-et', 'tigrinya-et-abegede', 'upper-alpha', 'upper-greek', 'upper-norwegian', 'upper-latin',
			/**
			* @see http://www.w3.org/TR/2002/WD-css3-lists-20021107/#symbolic
			*/
			'asterisks', 'footnotes',
			/**
			* @see http://www.w3.org/TR/2002/WD-css3-lists-20021107/#non-repeating
			*/
			'circled-decimal', 'circled-lower-latin', 'circled-upper-latin', 'dotted-decimal', 'double-circled-decimal', 'filled-circled-decimal', 'parenthesised-decimal', 'parenthesised-lower-latin'
		);

		// [LIST]
		$this->cb->BBCodes->addBBCode('LIST', array(
			'defaultAttr' => 'style',
			'trimBefore'   => true,
			'trimAfter'    => true,
			'ltrimContent' => true,
			'rtrimContent' => true
		));

		$this->cb->addTagAttribute('LIST', 'start', 'uint', array('isRequired' => false));

		$this->cb->addTagAttribute('LIST', 'style', 'regexp', array(
			'default'    => 'disc',
			'isRequired' => false,
			'regexp'     => '/^' . ConfigBuilder::buildRegexpFromList($styles) . '$/iD'
		));

		$this->cb->setTagTemplate(
			'LIST',
			'<ol>
				<xsl:attribute name="style">list-style-type:<xsl:choose>
					<xsl:when test="@style=\'1\'">decimal</xsl:when>
					<xsl:when test="@style=\'01\'">decimal-leading-zero</xsl:when>
					<xsl:when test="@style=\'a\'">lower-alpha</xsl:when>
					<xsl:when test="@style=\'A\'">upper-alpha</xsl:when>
					<xsl:when test="@style=\'i\'">lower-roman</xsl:when>
					<xsl:when test="@style=\'I\'">upper-roman</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="@style"/>
					</xsl:otherwise>
				</xsl:choose></xsl:attribute>

				<xsl:if test="@start">
					<xsl:attribute name="start">
						<xsl:value-of select="@start"/>
					</xsl:attribute>
				</xsl:if>

				<xsl:apply-templates/>
			</ol>'
		);

		// [*] maps to <LI>
		$this->cb->BBCodes->addBBCode('*', array(
			'tagName'      => 'LI',
			'trimBefore'   => true,
			'trimAfter'    => true,
			'ltrimContent' => true,
			'rtrimContent' => true
		));

		// [*] should only be used directly under [LIST]
		$this->cb->addTagRule('LI', 'requireParent', 'LIST');

		// also, let's make so that when we have two consecutive [*] we close
		// the first one when opening the second, instead of it behind its child
		$this->cb->addTagRule('LI', 'closeParent', 'LI');

		$this->cb->setTagTemplate('LI', '<li><xsl:apply-templates/></li>');
	}

	/**
	* Accepts both URLs and identifiers:
	*
	* [GOOGLEVIDEO]-4381488634998231167[/GOOGLEVIDEO]
	* [GOOGLEVIDEO]http://video.google.com/videoplay?docid=-4381488634998231167[/GOOGLEVIDEO]
	*/
	public function addGOOGLEVIDEO()
	{
		$regexp =
			'/^(?:' . preg_quote('http://video.google.com/videoplay?docid=', '/') . ')?(-?\\d+)/';

		$this->cb->BBCodes->addBBCodeFromExample(
			'[googlevideo]{REGEXP=' . $regexp . ';replace=$1}[/googlevideo]',
			'<object type="application/x-shockwave-flash" data="http://video.google.com/googleplayer.swf?docId={REGEXP}" width="400" height="326">
				<param name="movie" value="http://video.google.com/googleplayer.swf?docId={REGEXP}"/>
				<param name="allowScriptAcess" value="sameDomain"/>
				<param name="quality" value="best"/>
				<param name="scale" value="noScale"/>
				<param name="salign" value="TL"/>
				<param name="FlashVars" value="playerMode=embedded"/>
			</object>'
		);
	}

	/**
	* Accepts both URLs and identifiers:
	*
	* [YOUTUBE]-cEzsCAzTak[/YOUTUBE]
	* [YOUTUBE]http://www.youtube.com/watch?v=-cEzsCAzTak&feature=channel[/YOUTUBE]
	*/
	public function addYOUTUBE()
	{
		// note, we capture 5 chars or more {5,} to avoid capturing "http"
		$regexp = '/^(?:http:\\/\\/[a-z]+\\.youtube\\.com\\/watch\\?v=)?'
		        . '([A-Za-z_0-9\\-]{5,})/';

		$this->cb->BBCodes->addBBCodeFromExample(
			'[youtube]{REGEXP=' . $regexp . ';replace=$1}[/youtube]',
			'<object type="application/x-shockwave-flash" data="http://www.youtube.com/v/{REGEXP}" width="425" height="350">
				<param name="movie" value="http://www.youtube.com/v/{REGEXP}" />
				<param name="wmode" value="transparent" />
			</object>'
		);
	}

	public function addALIGN()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[align={CHOICE=left,right,center,justify}]{TEXT}[/align]',
			'<div style="text-align:{CHOICE}">{TEXT}</div>'
		);
	}

	public function addLEFT()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[left]{TEXT}[/left]',
			'<div style="text-align:left">{TEXT}</div>'
		);
	}

	public function addRIGHT()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[right]{TEXT}[/right]',
			'<div style="text-align:right">{TEXT}</div>'
		);
	}

	public function addCENTER()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[center]{TEXT}[/center]',
			'<div style="text-align:center">{TEXT}</div>'
		);
	}

	public function addJUSTIFY()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[justify]{TEXT}[/justify]',
			'<div style="text-align:justify">{TEXT}</div>'
		);
	}

	public function addBACKGROUND()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[background={COLOR}]{TEXT}[/background]',
			'<span style="background-color:{COLOR}">{TEXT}</span>'
		);
	}

	public function addFONT()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[font={SIMPLETEXT}]{TEXT}[/font]',
			'<span style="font-family:{SIMPLETEXT}">{TEXT}</span>'
		);
	}

	public function addBLINK()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[blink]{TEXT}[/blink]',
			'<span style="text-decoration:blink">{TEXT}</span>'
		);
	}

	public function addSUB()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[sub]{TEXT}[/sub]',
			'<span style="vertical-align:sub">{TEXT}</span>'
		);
	}

	public function addSUPER()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[super]{TEXT}[/super]',
			'<span style="vertical-align:super">{TEXT}</span>'
		);
	}

	/**
	* Basic [TABLE], [TR], [TH] and [TD] tags.
	* [TD] accepts two optional arguments: colspan and rowspan.
	*
	* Misplaced text, e.g. [TR]xxx[TD][/TD][/TR], is parsed normally but doesn't appear in the
	* HTML ouput.
	*/
	public function addTABLE()
	{
		// limit table nesting to 2, which should be enough for everybody
		$this->cb->BBCodes->addBBCode('TABLE', array('nestingLimit' => 2));
		$this->cb->setTagTemplate(
			'TABLE',
			'<table>
				<xsl:apply-templates select="COL" />
				<xsl:apply-templates select="TR" />
			</table>'
		);

		$this->cb->BBCodes->addBBCode('COL', array(
			'defaultRule' => 'deny',
			'autoClose'   => true
		));
		$this->cb->addTagRule('COL', 'requireParent', 'TABLE');
		$this->cb->addTagAttribute('COL', 'align', 'regexp', array(
			'isRequired' => false,
			'regexp'      => '/^(?:left|right|center|align)$/iD'
		));
		$this->cb->setTagTemplate(
			'COL',
			'<col>
				<xsl:if test="@align">
					<xsl:attribute name="style">text-align:<xsl:value-of select="@align" /></xsl:attribute>
				</xsl:if>
			</col>'
		);

		$this->cb->BBCodes->addBBCode('TR');
		$this->cb->addTagRule('TR', 'requireParent', 'TABLE');
		$this->cb->setTagTemplate(
			'TR',
			'<tr>
				<xsl:apply-templates select="TD | TH" />
			</tr>'
		);

		$this->cb->BBCodes->addBBCode('TH');
		$this->cb->addTagRule('TH', 'requireParent', 'TR');
		$this->cb->addTagAttribute('TH', 'colspan', 'uint', array('isRequired' => false));
		$this->cb->addTagAttribute('TH', 'rowspan', 'uint', array('isRequired' => false));
		$this->cb->setTagTemplate(
			'TH',
			'<th>
				<xsl:if test="@colspan">
					<xsl:attribute name="colspan">
						<xsl:value-of select="@colspan" />
					</xsl:attribute>
				</xsl:if>

				<xsl:if test="@rowspan">
					<xsl:attribute name="rowspan">
						<xsl:value-of select="@rowspan" />
					</xsl:attribute>
				</xsl:if>

				<xsl:apply-templates />
			</th>'
		);

		$this->cb->BBCodes->addBBCode('TD');
		$this->cb->addTagRule('TD', 'requireParent', 'TR');
		$this->cb->addTagAttribute('TD', 'colspan', 'uint', array('isRequired' => false));
		$this->cb->addTagAttribute('TD', 'rowspan', 'uint', array('isRequired' => false));
		$this->cb->setTagTemplate(
			'TD',
			'<td>
				<xsl:if test="@colspan">
					<xsl:attribute name="colspan">
						<xsl:value-of select="@colspan" />
					</xsl:attribute>
				</xsl:if>

				<xsl:if test="@rowspan">
					<xsl:attribute name="rowspan">
						<xsl:value-of select="@rowspan" />
					</xsl:attribute>
				</xsl:if>

				<xsl:apply-templates />
			</td>'
		);
	}

	/**
	* A simple implementation of a [CODE] tag
	*
	* It has one default, optional parameter "stx" and it's designed to work with Alex Gorbatchev's
	* SyntaxHighlighter library. See getCODEstx() for an example of how to retrieve the list of
	* syntaxes used so that you can load the appropriate brushes.
	*
	* @see  getCODEstx
	* @link http://alexgorbatchev.com/SyntaxHighlighter/
	*/
	public function addCODE()
	{
		$this->cb->BBCodes->addBBCode('CODE', array(
			'defaultRule'  => 'deny',
			'defaultAttr' => 'stx'
		));

		$this->cb->addTagAttribute('CODE', 'stx', 'identifier', array(
			'isRequired' => false,
			'preFilter'  => array('strtolower')
		));

		$this->cb->setTagTemplate(
			'CODE',
			'<pre class="brush:{@stx}"><xsl:value-of select="text()" /></pre>'
		);
	}

	static public function getCODEstx($xml)
	{
		// array_values() will reset the keys so that there's no gap in numbering, just in case
		return array_values(array_unique(
			array_map(
				'strval',
				simplexml_load_string($xml)->xpath('//CODE/@stx')
			)
		));
	}

	public function addHR()
	{
		$this->cb->BBCodes->addBBCode('HR', array(
			'defaultRule' => 'deny',
			'autoClose'   => true,
			'trimBefore'  => true,
			'trimAfter'   => true
		));

		$this->cb->setTagTemplate('HR', '<hr/>');
	}

	/**
	* Classic [QUOTE] tag
	*
	* The author can be specified in the default param.
	* You can limit the nesting level (which is set to 3 by default) and you can localize the author
	* string.
	* The markup used is intentionally compatible with phpBB themes.
	*
	* @param integer $nestingLevel
	* @param string  $authorStr
	*/
	public function addQUOTE($nestingLevel = 3, $authorStr = '%s wrote:')
	{
		$this->cb->BBCodes->addBBCode('QUOTE', array(
			'nestingLimit' => $nestingLevel,
			'defaultAttr' => 'author',
			'trimBefore'   => true,
			'trimAfter'    => true,
			'ltrimContent' => true,
			'rtrimContent' => true
		));

		$authorXml = str_replace(
			'%s',
			'<xsl:value-of select="@author" />',
			htmlspecialchars($authorStr)
		);

		$this->cb->addTagAttribute('QUOTE', 'author', 'text', array('isRequired' => false));
		$this->cb->setTagTemplate(
			'QUOTE',
			'<xsl:choose>
				<xsl:when test="@author">

					<blockquote>
						<div>
							<cite>' . $authorXml . '</cite>
							<xsl:apply-templates />
						</div>
					</blockquote>

				</xsl:when>
				<xsl:otherwise>

					<blockquote class="uncited">
						<div>
							<xsl:apply-templates />
						</div>
					</blockquote>

				</xsl:otherwise>
			</xsl:choose>'
		);
	}

	/**
	* [EMAIL] tag with an optional "subject" parameter
	*
	* [EMAIL]user@example.org[/EMAIL]
	* [EMAIL=user@example.org]email me![/EMAIL]
	*
	* This tag uses tricks and hacks all over the place. A "compound" attribute named "content" is
	* used to copy the tag's content into two other attributes so that it can be used in two
	* different ways (see below.) The link starts as a single hash "#" and some Javascript is used
	* to change it to the relevant "mailto:" URL. The content of the tag is reversed twice, once in
	* PHP with strrev() then in CSS, so that the email doesn't appear in clear in the HTML source.
	* The idea comes from a 2008 article from tillate.com (link below.) Weirdly enough, the HTML
	* generated successfully validates as HTML 4.01 Strict, XHTML 1.0 Strict and HTML5.
	*
	* @link http://techblog.tilllate.com/2008/07/20/ten-methods-to-obfuscate-e-mail-addresses-compar
	*/
	public function addEMAIL()
	{
		$this->cb->BBCodes->addBBCode('EMAIL', array(
			'defaultAttr' => 'email',
			'contentAttr' => 'content',
			'defaultRule' => 'deny',
			'attrs' => array(
				'email'   => array(
					'type' => 'email',
					'postFilter' => array('strrev')
				),
				'subject' => array(
					'type' => 'text',
					'isRequired' => false,
					'postFilter' => array('rawurlencode', 'strrev')
				),
				/**
				* We set the "content" attribute as a compound attribute with a regexp that will
				* match virtually anything. Its value will be used for the "email" attribute if
				* the latter was not provided. The idea is to have a "content" attribute that is
				* filled with the tag's content and copy its value to "email" and "revtext" so that
				* they receive a different treatment via validation/postFilter
				*/
				'content' => array(
					'type'   => 'compound',
					'regexp' => '/(?P<revtext>(?P<email>.*))/'
				),
				'revtext' => array(
					'type' => 'text',
					'postFilter' => array('strrev')
				)
			)
		));

		$this->cb->setTagTemplate(
			'EMAIL',
			'<a href="javascript:" style="unicode-bidi:bidi-override;direction:rtl" onfocus="this.onmouseover()">
				<xsl:attribute name="onmouseover">
					<xsl:text>this.href=\'</xsl:text>
					<xsl:if test="@subject">
						<xsl:value-of select="@subject" />
						<xsl:text>=tcejbus?</xsl:text>
					</xsl:if>
					<xsl:value-of select="@email" />
					<xsl:text>:otliam\'.split(\'\').reverse().join(\'\')</xsl:text>
				</xsl:attribute>

				<xsl:value-of select="@revtext" />
			</a>'
		);
	}

	/**
	* Accepts both URLs and identifiers:
	*
	* [JUSTIN]justin[/JUSTIN]
	* [JUSTIN]http://www.justin.tv/justin[/JUSTIN]
	*/
	public function addJUSTIN()
	{
		$regexp = '/^(?:http:\\/\\/www\\.justin\\.tv\\/)?([A-Za-z_0-9]+)/';

		$this->cb->BBCodes->addBBCodeFromExample(
			'[JUSTIN]{REGEXP=' . $regexp . ';replace=$1}[/JUSTIN]',
			'<object type="application/x-shockwave-flash" height="300" width="400"  data="http://www.justin.tv/widgets/live_embed_player.swf?channel={REGEXP}" bgcolor="#000000">
				<param name="allowFullScreen" value="true" />
				<param name="allowScriptAccess" value="always" />
				<param name="allowNetworking" value="all" />
				<param name="movie" value="http://www.justin.tv/widgets/live_embed_player.swf" />
				<param name="flashvars" value="channel={REGEXP}&amp;auto_play=false" />
			</object>'
		);
	}

	/**
	* Display a date using browser's locale via Javascript
	*
	* e.g. [LOCALTIME]2005/09/17 12:55:09 PST[/LOCALTIME]
	*
	* The date is parsed in PHP with strtotime(), which is used as a pre-filter, then it is
	* validated as a number. strtotime() returns false on invalid date, so it invalid dates will be
	* automatically rejected.
	*
	* Using user-supplied data in <script> tags is disallowed by ConfigBuilder by default, and the
	* limitation has to be removed by using the third parameter. The template should still be
	* secure, though, as only numbers are allowed and it should be impossible to inject any
	* Javascript using the [LOCALTIME] BBCode.
	*
	* Finally, if Javascript is disabled, the original content is displayed via a <noscript> tag.
	*
	* Note the use of <xsl:apply-templates/> instead of the {NUMBER} placeholder. This is because
	* {NUMBER} will display the value returned by strtotime() whereas <xsl:apply-templates/> will
	* display the UNFILTERED value.
	*/
	public function addLOCALTIME()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[LOCALTIME]{NUMBER;preFilter=strtotime}[/LOCALTIME]',
			'<span class="localtime" title="{text()}">
				<script type="text/javascript">document.write(new Date({NUMBER}*1000).toLocaleString())</script>
				<noscript><xsl:apply-templates /></noscript>
			</span>',
			ConfigBuilder::ALLOW_INSECURE_TEMPLATES
		);
	}

	/**
	* Basic [SPOILER] tag
	*
	* It is unstyled, you have to style it yourself. Each section was given a nice class name for
	* that purpose.
	*
	* Note that because of XSL, curly braces { } inside of attribute values have to be escaped.
	* You can escape them by having two of them, e.g. "if (true) {{ dostuff(); }}"
	*/
	public function addSPOILER($spoilerStr = 'Spoiler:', $showStr = 'Show', $hideStr = 'Hide')
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[SPOILER={TEXT1;isRequired=0}]{TEXT2}[/SPOILER]',
			'<div class="spoiler">
				<div class="spoiler-header">
					<input type="button" value="' . $showStr . '" onclick="'
						. 'var s=this.parentNode.nextSibling.style;'
						. "if(s.display!=''){{s.display='';this.value='" . $hideStr . "'}}"
						. "else{{s.display='none';this.value='" . $showStr . "'}}"
					. '"/>
					<span class="spoiler-title">' . $spoilerStr . ' {TEXT1}</span>
				</div>
				<div class="spoiler-content" style="display:none">{TEXT2}</div>
			</div>'
		);
	}

	public function addCOLOR()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[COLOR={COLOR}]{TEXT}[/COLOR]',
			'<span style="color:{COLOR}">{TEXT}</span>'
		);
	}

	/**
	* [SIZE] tag with size expressed in %
	*
	* Note that we don't allow [SIZE] tags to be nested in order to prevent users for exceeding the
	* size limits
	*
	* @param integer $minSize  Minimum size
	* @param integer $maxnSize Maximum size
	*/
	public function addSIZE($minSize = 50, $maxSize = 200)
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[SIZE={RANGE=' . $minSize . ',' . $maxSize . '}]{TEXT}[/SIZE]',
			'<span style="font-size:{RANGE}%">{TEXT}</span>',
			0,
			array('nestingLimit' => 1)
		);
	}

	public function addBLIP()
	{
		$regexp = '/^(?:' . preg_quote('http://blip.tv/file/', '/') . ')?([0-9]+)/';

		// HTML taken straight from Blip's player "Copy embed code" feature
		$this->cb->BBCodes->addBBCodeFromExample(
			'[BLIP]{REGEXP=' . $regexp . ';replace=$1}[/BLIP]',
			'<embed src="http://blip.tv/play/{REGEXP}" type="application/x-shockwave-flash" width="480" height="300" allowscriptaccess="always" allowfullscreen="true"></embed>'
		);
	}

	public function addVIMEO()
	{
		$regexp = '/^(?:' . preg_quote('http://vimeo.com/', '/') . ')?([0-9]+)/';

		// HTML taken straight from Vimeo's player "EMBED" feature
		$this->cb->BBCodes->addBBCodeFromExample(
			'[VIMEO]{REGEXP=' . $regexp . ';replace=$1}[/VIMEO]',
			'<iframe src="http://player.vimeo.com/video/{REGEXP}" width="400" height="225" frameborder="0"></iframe>'
		);
	}

	public function addDAILYMOTION()
	{
		$regexp = '/^(?:' . preg_quote('http://www.dailymotion.com/video/', '/') . ')?([0-9a-z]+)/';

		// HTML taken straight from Dailymotion's Export->embed feature
		$this->cb->BBCodes->addBBCodeFromExample(
			'[DAILYMOTION]{REGEXP=' . $regexp . ';replace=$1}[/DAILYMOTION]',
			'<object width="480" height="270">
				<param name="movie" value="http://www.dailymotion.com/swf/video/{REGEXP}"></param>
				<param name="allowFullScreen" value="true"></param>
				<param name="allowScriptAccess" value="always"></param>
				
				<embed type="application/x-shockwave-flash" src="http://www.dailymotion.com/swf/video/{REGEXP}" width="480" height="270" allowfullscreen="true" allowscriptaccess="always"></embed>
			</object>'
		);
	}

	public function addINS()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[INS]{TEXT}[/INS]',
			'<ins>{TEXT}</ins>'
		);
	}

	public function addDEL()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[DEL]{TEXT}[/DEL]',
			'<del>{TEXT}</del>'
		);
	}

	/**
	* [FLASH] tag
	*
	* Should be compatible with phpBB's [flash] tag.
	*
	* "allowScriptAccess" is set to "never"
	* "allowNetworking" is set to "internal" -- not the most restrictive setting, but that's what
	* people seem to generally use
	*
	* The rest was based off Adobe's site.
	*
	* @link http://kb2.adobe.com/cps/164/tn_16494.html
	* @link http://help.adobe.com/en_US/ActionScript/3.0_ProgrammingAS3/WS1EFE2EDA-026D-4d14-864E-79DFD56F87C6.html
	*/
	public function addFLASH()
	{
		$this->cb->BBCodes->addBBCode(
			'FLASH',
			array(
				'defaultAttr' => 'dimensions',
				'contentAttr' => 'url',
				'attrs' => array(
					'width'  => array('type' => 'number'),
					'height' => array('type' => 'number'),
					'url'    => array('type' => 'url'),
					'dimensions' => array(
						'type'       => 'compound',
						'regexp'     => '/(?P<width>[0-9]+),(?P<height>[0-9]+)/',
						'isRequired' => false
					)
				),
				'template' =>
					'<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://fpdownload.macromedia.com/get/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="{@width}" height="{@height}">
						<param name="movie" value="{@url}" />
						<param name="quality" value="high" />
						<param name="wmode" value="opaque" />
						<param name="play" value="false" />
						<param name="loop" value="false" />

						<param name="allowScriptAccess" value="never" />
						<param name="allowNetworking" value="internal" />

						<embed src="{@url}" quality="high" width="{@width}" height="{@height}" wmode="opaque" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" play="false" loop="false" allowscriptaccess="never" allownetworking="internal"></embed>
					</object>'
			)
		);
	}
}