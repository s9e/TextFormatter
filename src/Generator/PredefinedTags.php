<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Generator;

use s9e\TextFormatter\Generator;

class PredefinedTags
{
	/**
	* @var Generator
	*/
	protected $generator;

	public function __construct(Generator $generator)
	{
		$this->generator = $generator;
	}

	public function addB()
	{
		$this->generator->addTag('B');
		$this->generator->setTagTemplate('B', '<b><xsl:apply-templates /></b>');
	}

	public function addI()
	{
		$this->generator->addTag('I');
		$this->generator->setTagTemplate('I', '<i><xsl:apply-templates /></i>');
	}

	public function addU()
	{
		$this->generator->addTag('U');
		$this->generator->setTagTemplate('U', '<span style="text-decoration:underline"><xsl:apply-templates /></span>');
	}

	public function addS()
	{
		$this->generator->addTag('S');
		$this->generator->setTagTemplate('S', '<s><xsl:apply-templates /></s>');
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
		$this->generator->addTag('URL');
		$this->generator->addTagRule('URL', 'denyChild', 'URL');
		$this->generator->addTagRule('URL', 'denyDescendant', 'URL');

		$this->generator->addAttribute('URL', 'url', '#url');
		$this->generator->addAttribute('URL', 'title', array('required' => false));

		$this->generator->setTagTemplate(
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
		$this->generator->addTag('IMG', array(
			'defaultChildRule' => 'deny',
			'defaultDescendantRule' => 'deny'
		));

		$this->generator->addAttribute('IMG', 'src', '#url');
		$this->generator->addAttribute('IMG', 'alt', array('required' => false));
		$this->generator->addAttribute('IMG', 'title', array('required' => false));

		$this->generator->setTagTemplate(
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

	/**
	* Note: <LIST> will only be transformed if it contains at least one <LI>
	*/
	public function addLIST()
	{
		// [LIST]
		$this->generator->addTag('LIST', array(
			'trimBefore'   => true,
			'trimAfter'    => true,
			'ltrimContent' => true,
			'rtrimContent' => true
		));

		$this->generator->addAttribute('LIST', 'start', array(
			'filter'   => '#uint',
			'required' => false
		));

		$this->generator->addAttribute('LIST', 'style', array(
			'filter'       => '#regexp',
			'defaultValue' => 'disc',
			'required'     => false,
			'regexp'       => '/^(?:[a-z\\-]+|[0-9]+)$/iD'
		));

		$this->generator->setTagXSL(
			'LIST',
			'<xsl:template match="LIST[LI]">
				<ol>
					<xsl:attribute name="style">list-style-type:<xsl:choose>
						<xsl:when test="contains(\'123456789\',substring(@style,1,1))">decimal</xsl:when>
						<xsl:when test="starts-with(@style,\'0\')">decimal-leading-zero</xsl:when>
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
				</ol>
			</xsl:template>'
		);

		$this->generator->addTag('LI', array(
			'trimBefore'   => true,
			'trimAfter'    => true,
			'ltrimContent' => true,
			'rtrimContent' => true
		));

		// [*] should only be used directly under [LIST]
		$this->generator->addTagRule('LI', 'requireParent', 'LIST');

		// also, let's make so that when we have two consecutive [*] we close
		// the first one when opening the second, instead of it behind its child
		$this->generator->addTagRule('LI', 'closeParent', 'LI');

		$this->generator->setTagTemplate('LI', '<li><xsl:apply-templates /></li>');

		// make [LIST] and [LI] play nice with the Paragrapher plugin
		$this->generator->addTagRule('LIST', 'denyChild', 'P');
		$this->generator->addTagRule('LIST', 'denyChild', 'BR');
		$this->generator->addTagRule('LI', 'denyChild', 'P');
		$this->generator->addTagRule('LI', 'denyChild', 'BR');
	}

	public function addALIGN()
	{
		$this->generator->addTag('ALIGN');
		$this->generator->addAttribute('ALIGN', 'align', array(
			'filter' => '#regexp',
			'regexp' => '#^(?:left|right|center|justify)$#Di'
		));
		$this->generator->setTagTemplate(
			'ALIGN', '<div style="text-align:{@align}"><xsl:apply-templates /></div>'
		);
	}

	public function addLEFT()
	{
		$this->generator->addTag('LEFT');
		$this->generator->setTagTemplate(
			'LEFT', '<div style="text-align:left"><xsl:apply-templates /></div>'
		);
	}

	public function addRIGHT()
	{
		$this->generator->addTag('RIGHT');
		$this->generator->setTagTemplate(
			'RIGHT', '<div style="text-align:right"><xsl:apply-templates /></div>'
		);
	}

	public function addCENTER()
	{
		$this->generator->addTag('CENTER');
		$this->generator->setTagTemplate(
			'CENTER', '<div style="text-align:center"><xsl:apply-templates /></div>'
		);
	}

	public function addJUSTIFY()
	{
		$this->generator->addTag('JUSTIFY');
		$this->generator->setTagTemplate(
			'JUSTIFY', '<div style="text-align:justify"><xsl:apply-templates /></div>'
		);
	}

	public function addSUB()
	{
		$this->generator->addTag('SUB');
		$this->generator->setTagTemplate('SUB', '<sub><xsl:apply-templates /></sub>');
	}

	public function addSUPER()
	{
		$this->generator->addTag('SUPER');
		$this->generator->setTagTemplate('SUPER', '<sup><xsl:apply-templates /></sup>');
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
		$this->generator->addTag('TABLE', array('nestingLimit' => 2));
		$this->generator->setTagTemplate(
			'TABLE',
			'<table>
				<xsl:apply-templates select="COL" />
				<xsl:apply-templates select="TR" />
			</table>'
		);

		$this->generator->addTag('COL', array(
			'defaultChildRule' => 'deny',
			'defaultDescendantRule' => 'deny'
		));
		$this->generator->addTagRule('COL', 'requireParent', 'TABLE');
		$this->generator->addAttribute('COL', 'align', array(
			'filter'   => '#regexp',
			'required' => false,
			'regexp'   => '/^(?:left|right|center|align)$/iD'
		));
		$this->generator->setTagTemplate(
			'COL',
			'<col>
				<xsl:if test="@align">
					<xsl:attribute name="style">text-align:<xsl:value-of select="@align" /></xsl:attribute>
				</xsl:if>
			</col>'
		);

		$this->generator->addTag('TR');
		$this->generator->addTagRule('TR', 'requireParent', 'TABLE');
		$this->generator->setTagTemplate(
			'TR',
			'<tr>
				<xsl:apply-templates select="TD | TH" />
			</tr>'
		);

		$this->generator->addTag('TH');
		$this->generator->addTagRule('TH', 'requireParent', 'TR');
		$this->generator->addAttribute('TH', 'colspan', array(
			'filter'   => '#uint',
			'required' => false
		));
		$this->generator->addAttribute('TH', 'rowspan', array(
			'filter'   => '#uint',
			'required' => false
		));
		$this->generator->setTagTemplate(
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

		$this->generator->addTag('TD');
		$this->generator->addTagRule('TD', 'requireParent', 'TR');
		$this->generator->addAttribute('TD', 'colspan', array(
			'filter'   => '#uint',
			'required' => false
		));
		$this->generator->addAttribute('TD', 'rowspan', array(
			'filter'   => '#uint',
			'required' => false
		));
		$this->generator->setTagTemplate(
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
	* SyntaxHighlighter library. See PredefinedTags::getUsedCodeStx() for an example of how to
	* retrieve the list of syntaxes used so that you can load the appropriate brushes.
	*
	* @see  getUsedCodeStx
	* @link http://alexgorbatchev.com/SyntaxHighlighter/
	*/
	public function addCODE()
	{
		$this->generator->addTag('CODE', array(
			'defaultChildRule' => 'deny',
			'defaultDescendantRule' => 'deny'
		));

		$this->generator->addAttribute('CODE', 'stx', array(
			'filterChain'  => array('strtolower', '#id'),
			'defaultValue' => 'plain'
		));

		$this->generator->setTagTemplate(
			'CODE',
			'<pre class="brush:{@stx}"><xsl:value-of select="text()" /></pre>'
		);
	}

	public static function getUsedCodeStx($xml)
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
		$this->generator->addTag('HR', array(
			'defaultChildRule' => 'deny',
			'defaultDescendantRule' => 'deny',
			'trimBefore'  => true,
			'trimAfter'   => true
		));

		$this->generator->setTagTemplate('HR', '<hr />');
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
		$this->generator->addTag('QUOTE', array(
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

		$this->generator->addAttribute('QUOTE', 'author', array('required' => false));
		$this->generator->setTagTemplate(
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
	* NOTE: the "mailto:" link is generated dynamically using onmouseover/onfocus events. This is 
	*       for two reasons: first, it doesn't have the performance concerns historically associated
	*       with document.write, and secondly it ensures at least some level of interaction. IOW, a
	*       bot using a scripted browser would have to be programmed to hover links in order to grab
	*       its mailto.
	*
	* @link http://techblog.tilllate.com/2008/07/20/ten-methods-to-obfuscate-e-mail-addresses-compar
	*/
	public function addEMAIL()
	{
		$this->generator->addTag('EMAIL', array(
			'defaultChildRule' => 'deny',
			'defaultDescendantRule' => 'deny',
			'attrs' => array(
				'email'   => array(
					'filterChain' => array('#email', 'strrev')
				),
				'subject' => array(
					'required' => false,
					'filterChain' => array('rawurlencode', 'strrev')
				),
				'revtext' => array(
					'filterChain' => array('strrev')
				)
			)
		));

		$tpl = <<<'EOT'
				<a href="javascript:" style="unicode-bidi:bidi-override;direction:rtl" onfocus="this.onmouseover()">
					<xsl:attribute name="onmouseover">
						<xsl:text>this.href='</xsl:text>
						<xsl:if test="@subject">
							<xsl:value-of select="@subject" />
							<xsl:text>=tcejbus?</xsl:text>
						</xsl:if>
						<xsl:value-of select="@email" />
						<xsl:text>:ot\u006ciam'.split('').reverse().join('')</xsl:text>
					</xsl:attribute>

					<xsl:value-of select="@revtext" />
				</a>
EOT;

		$this->generator->setTagTemplate('EMAIL', $tpl, Generator::ALLOW_UNSAFE_TEMPLATES);
	}

	public function addCOLOR()
	{
		$this->generator->addTag('COLOR');
		$this->generator->addAttribute('COLOR', 'color', '#color');
		$this->generator->setTagTemplate(
			'COLOR', '<span style="color:{@color}"><xsl:apply-templates /></span>'
		);
	}

	public function addINS()
	{
		$this->generator->addTag('INS');
		$this->generator->setTagTemplate('INS', '<ins><xsl:apply-templates /></ins>');
	}

	public function addDEL()
	{
		$this->generator->addTag('DEL');
		$this->generator->setTagTemplate('DEL', '<del><xsl:apply-templates /></del>');
	}

	public function addEM()
	{
		$this->generator->addTag('EM');
		$this->generator->setTagTemplate('EM', '<em><xsl:apply-templates /></em>');
	}

	public function addSTRONG()
	{
		$this->generator->addTag('STRONG');
		$this->generator->setTagTemplate('STRONG', '<strong><xsl:apply-templates /></strong>');
	}

	public function addSPAN()
	{
		$this->generator->addTag('SPAN');

		$this->generator->addAttribute('SPAN', 'class', array(
			'filter'   => '#regexp',
			'required' => false,
			'regexp'   => '/^[a-z_0-9 ]+$/Di'
		));

		$this->generator->setTagTemplate(
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
		$this->generator->addTag('NOPARSE', array(
			'defaultChildRule' => 'deny',
			'defaultDescendantRule' => 'deny'
		));
		$this->generator->setTagTemplate(
			'NOPARSE',
			'<xsl:value-of select="text()" />'
		);
	}

	public function addCITE()
	{
		$this->generator->addTag('CITE');
		$this->generator->setTagTemplate('CITE', '<cite><xsl:apply-templates /></cite>');
	}

	public function addACRONYM()
	{
		$this->generator->addTag('ACRONYM');
		$this->generator->addAttribute('ACRONYM', 'title');
		$this->generator->setTagTemplate('ACRONYM', '<acronym title="{@title}"><xsl:apply-templates /></acronym>');
	}

	public function addH1()
	{
		$this->generator->addTag('H1');
		$this->generator->setTagTemplate('H1', '<h1><xsl:apply-templates /></h1>');
	}

	public function addH2()
	{
		$this->generator->addTag('H2');
		$this->generator->setTagTemplate('H2', '<h2><xsl:apply-templates /></h2>');
	}

	public function addH3()
	{
		$this->generator->addTag('H3');
		$this->generator->setTagTemplate('H3', '<h3><xsl:apply-templates /></h3>');
	}

	public function addH4()
	{
		$this->generator->addTag('H4');
		$this->generator->setTagTemplate('H4', '<h4><xsl:apply-templates /></h4>');
	}

	public function addH5()
	{
		$this->generator->addTag('H5');
		$this->generator->setTagTemplate('H5', '<h5><xsl:apply-templates /></h5>');
	}

	public function addH6()
	{
		$this->generator->addTag('H6');
		$this->generator->setTagTemplate('H6', '<h6><xsl:apply-templates /></h6>');
	}

	public function addDL()
	{
		$this->generator->addTag('DL', array(
			'trimBefore'   => true,
			'trimAfter'    => true,
			'ltrimContent' => true,
			'rtrimContent' => true
		));

		$this->generator->setTagTemplate('DL', '<dl><xsl:apply-templates select="DT | DD" /></dl>');

		foreach (array('DT', 'DD') as $tagName)
		{
			$this->generator->addTag($tagName, array(
				'trimBefore'   => true,
				'trimAfter'    => true,
				'ltrimContent' => true,
				'rtrimContent' => true
			));

			$this->generator->addTagRule($tagName, 'requireParent', 'DL');
			$this->generator->addTagRule($tagName, 'closeParent', 'DT');
			$this->generator->addTagRule($tagName, 'closeParent', 'DD');
		}

		$this->generator->setTagTemplate('DT', '<dt><xsl:apply-templates /></dt>');
		$this->generator->setTagTemplate('DD', '<dd><xsl:apply-templates /></dd>');
	}

	public function addFLOAT()
	{
		$this->generator->addTag('FLOAT', array(
			'trimAfter'  => true
		));
		$this->generator->addAttribute('FLOAT', 'float', array(
			'filter' => '#regexp',
			'regexp' => '#^(?:left|right|none)$#Di'
		));
		$this->generator->setTagTemplate(
			'FLOAT', '<div style="float:{@float}"><xsl:apply-templates /></div>'
		);
	}

	public function addCLEAR()
	{
		$this->generator->addTag('CLEAR');
		$this->generator->addAttribute('CLEAR', 'clear', array(
			'filter' => '#regexp',
			'regexp' => '#^(?:left|right|both)$#Di',
			'defaultValue' => 'both'
		));
		$this->generator->setTagTemplate(
			'CLEAR', '<div style="clear:{@clear}"><xsl:apply-templates /></div>'
		);
	}
}