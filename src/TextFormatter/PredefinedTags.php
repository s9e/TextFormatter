<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter;

class PredefinedTags
{
	/**
	* @var ConfigBuilder
	*/
	protected $cb;

	public function __construct(ConfigBuilder $cb)
	{
		$this->cb = $cb;
	}

	public function addB()
	{
		$this->cb->addTag('B');
		$this->cb->setTagTemplate('B', '<b><xsl:apply-templates /></b>');
	}

	public function addI()
	{
		$this->cb->addTag('I');
		$this->cb->setTagTemplate('I', '<i><xsl:apply-templates /></i>');
	}

	public function addU()
	{
		$this->cb->addTag('U');
		$this->cb->setTagTemplate('U', '<span style="text-decoration:underline"><xsl:apply-templates /></span>');
	}

	public function addS()
	{
		$this->cb->addTag('S');
		$this->cb->setTagTemplate('S', '<span style="text-decoration:line-through"><xsl:apply-templates /></span>');
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
		$this->cb->addTag('URL');

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
				<xsl:apply-templates />
			</a>'
		);
	}

	/**
	* IMG tag with optional support for "title" and "alt"
	*
	* Note that no attempt is made to verify that the image's source is actually an image.
	*/
	public function addIMG()
	{
		$this->cb->addTag('IMG', array(
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
		$this->cb->addTag('LIST', array(
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
						<xsl:value-of select="@style" />
					</xsl:otherwise>
				</xsl:choose></xsl:attribute>

				<xsl:if test="@start">
					<xsl:attribute name="start">
						<xsl:value-of select="@start" />
					</xsl:attribute>
				</xsl:if>

				<xsl:apply-templates />
			</ol>'
		);

		$this->cb->addTag('LI', array(
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

		$this->cb->setTagTemplate('LI', '<li><xsl:apply-templates /></li>');
	}

	public function addALIGN()
	{
		$this->cb->addTag('ALIGN');
		$this->cb->addTagAttribute(
			'ALIGN', 'align', 'regexp', array('regexp' => '#^(?:left|right|center|justify)$#Di')
		);
		$this->cb->setTagTemplate(
			'ALIGN', '<div style="text-align:{@align}"><xsl:apply-templates /></div>'
		);
	}

	public function addLEFT()
	{
		$this->cb->addTag('LEFT');
		$this->cb->setTagTemplate(
			'LEFT', '<div style="text-align:left"><xsl:apply-templates /></div>'
		);
	}

	public function addRIGHT()
	{
		$this->cb->addTag('RIGHT');
		$this->cb->setTagTemplate(
			'RIGHT', '<div style="text-align:right"><xsl:apply-templates /></div>'
		);
	}

	public function addCENTER()
	{
		$this->cb->addTag('CENTER');
		$this->cb->setTagTemplate(
			'CENTER', '<div style="text-align:center"><xsl:apply-templates /></div>'
		);
	}

	public function addJUSTIFY()
	{
		$this->cb->addTag('JUSTIFY');
		$this->cb->setTagTemplate(
			'JUSTIFY', '<div style="text-align:justify"><xsl:apply-templates /></div>'
		);
	}

	public function addSUB()
	{
		$this->cb->addTag('SUB');
		$this->cb->setTagTemplate('SUB', '<sub><xsl:apply-templates /></sub>');
	}

	public function addSUPER()
	{
		$this->cb->addTag('SUPER');
		$this->cb->setTagTemplate('SUPER', '<sup><xsl:apply-templates /></sup>');
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
		$this->cb->addTag('TABLE', array('nestingLimit' => 2));
		$this->cb->setTagTemplate(
			'TABLE',
			'<table>
				<xsl:apply-templates select="COL" />
				<xsl:apply-templates select="TR" />
			</table>'
		);

		$this->cb->addTag('COL', array(
			'defaultRule' => 'deny'
		));
		$this->cb->addTagRule('COL', 'requireParent', 'TABLE');
		$this->cb->addTagAttribute('COL', 'align', 'regexp', array(
			'isRequired' => false,
			'regexp'     => '/^(?:left|right|center|align)$/iD'
		));
		$this->cb->setTagTemplate(
			'COL',
			'<col>
				<xsl:if test="@align">
					<xsl:attribute name="style">text-align:<xsl:value-of select="@align" /></xsl:attribute>
				</xsl:if>
			</col>'
		);

		$this->cb->addTag('TR');
		$this->cb->addTagRule('TR', 'requireParent', 'TABLE');
		$this->cb->setTagTemplate(
			'TR',
			'<tr>
				<xsl:apply-templates select="TD | TH" />
			</tr>'
		);

		$this->cb->addTag('TH');
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

		$this->cb->addTag('TD');
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
		$this->cb->addTag('CODE', array(
			'defaultRule' => 'deny',
		));

		$this->cb->addTagAttribute('CODE', 'stx', 'identifier', array(
			'default'    => 'plain',
			'isRequired' => false,
			'preFilter'  => array(
				array('callback' => 'strtolower')
			)
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
		$this->cb->addTag('HR', array(
			'defaultRule' => 'deny',
			'trimBefore'  => true,
			'trimAfter'   => true
		));

		$this->cb->setTagTemplate('HR', '<hr />');
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
		$this->cb->addTag('QUOTE', array(
			'nestingLimit' => $nestingLevel,
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
		$this->cb->addTag('EMAIL', array(
			'defaultRule' => 'deny',
			'attrs' => array(
				'email'   => array(
					'type' => 'email',
					'postFilter' => array(
						array('callback' => 'strrev')
					)
				),
				'subject' => array(
					'type' => 'text',
					'isRequired' => false,
					'postFilter' => array(
						array('callback' => 'rawurlencode'),
						array('callback' => 'strrev')
					)
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
					'regexp' => '/(?P<revtext>(?P<email>.*))/s'
				),
				'revtext' => array(
					'type' => 'text',
					'postFilter' => array(
						array('callback' => 'strrev')
					)
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

	public function addCOLOR()
	{
		$this->cb->addTag('COLOR');
		$this->cb->addTagAttribute('COLOR', 'color', 'color');
		$this->cb->setTagTemplate(
			'COLOR', '<span style="color:{@color}"><xsl:apply-templates /></span>'
		);
	}

	public function addINS()
	{
		$this->cb->addTag('INS');
		$this->cb->setTagTemplate('INS', '<ins><xsl:apply-templates /></ins>');
	}

	public function addDEL()
	{
		$this->cb->addTag('DEL');
		$this->cb->setTagTemplate('DEL', '<del><xsl:apply-templates /></del>');
	}

	public function addEM()
	{
		$this->cb->addTag('EM');
		$this->cb->setTagTemplate('EM', '<em><xsl:apply-templates /></em>');
	}

	public function addSTRONG()
	{
		$this->cb->addTag('STRONG');
		$this->cb->setTagTemplate('STRONG', '<strong><xsl:apply-templates /></strong>');
	}

	public function addSPAN()
	{
		$this->cb->addTag('SPAN');

		$this->cb->addTagAttribute('SPAN', 'class', 'regexp', array(
			'isRequired' => false,
			'regexp' => '/^[a-z_0-9 ]+$/Di'
		));

		$this->cb->setTagTemplate(
			'SPAN',
			'<span>
				<xsl:if test="@class">
					<xsl:attribute name="class">
						<xsl:value-of select="@class" />
					</xsl:attribute>
				</xsl:if>
				<xsl:apply-templates />
			</span>'
		);
	}

	public function addNOPARSE()
	{
		$this->cb->addTag('NOPARSE', array('defaultRule' => 'deny'));
		$this->cb->setTagTemplate(
			'NOPARSE',
			'<xsl:value-of select="text()" />'
		);
	}

	/**
	* @todo allow/deny the appropriate tags
	*/
	public function addCITE()
	{
		$this->cb->addTag('CITE');
		$this->cb->setTagTemplate('CITE', '<cite><xsl:apply-templates /></cite>');
	}

	public function addACRONYM()
	{
		$this->cb->addTag('ACRONYM');
		$this->cb->addTagAttribute('ACRONYM', 'title', 'text');
		$this->cb->setTagTemplate('ACRONYM', '<acronym title="{@title}"><xsl:apply-templates /></acronym>');
	}

	/**
	* @todo allow/deny the appropriate tags
	*/
	public function addH1()
	{
		$this->cb->addTag('H1');
		$this->cb->setTagTemplate('H1', '<h1><xsl:apply-templates /></h1>');
	}

	public function addH2()
	{
		$this->cb->addTag('H2');
		$this->cb->setTagTemplate('H2', '<h2><xsl:apply-templates /></h2>');
	}

	public function addH3()
	{
		$this->cb->addTag('H3');
		$this->cb->setTagTemplate('H3', '<h3><xsl:apply-templates /></h3>');
	}

	public function addH4()
	{
		$this->cb->addTag('H4');
		$this->cb->setTagTemplate('H4', '<h4><xsl:apply-templates /></h4>');
	}

	public function addH5()
	{
		$this->cb->addTag('H5');
		$this->cb->setTagTemplate('H5', '<h5><xsl:apply-templates /></h5>');
	}

	public function addH6()
	{
		$this->cb->addTag('H6');
		$this->cb->setTagTemplate('H6', '<h6><xsl:apply-templates /></h6>');
	}

	public function addDL()
	{
		$this->cb->addTag('DL', array(
			'trimBefore'   => true,
			'trimAfter'    => true,
			'ltrimContent' => true,
			'rtrimContent' => true
		));

		$this->cb->setTagTemplate('DL', '<dl><xsl:apply-templates select="DT | DD" /></dl>');

		foreach (array('DT', 'DD') as $tagName)
		{
			$this->cb->addTag($tagName, array(
				'trimBefore'   => true,
				'trimAfter'    => true,
				'ltrimContent' => true,
				'rtrimContent' => true
			));

			$this->cb->addTagRule($tagName, 'requireParent', 'DL');
			$this->cb->addTagRule($tagName, 'closeParent', 'DT');
			$this->cb->addTagRule($tagName, 'closeParent', 'DD');
		}

		$this->cb->setTagTemplate('DT', '<dt><xsl:apply-templates /></dt>');
		$this->cb->setTagTemplate('DD', '<dd><xsl:apply-templates /></dd>');
	}
}