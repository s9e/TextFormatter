<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use s9e\TextFormatter\Configurator;

class PredefinedTags
{
	/**
	* @var Configurator
	*/
	protected $configurator;

	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
	}

	public function addB()
	{
		$this->configurator->addTag('B');
		$this->configurator->setTagTemplate('B', '<b><xsl:apply-templates /></b>');
	}

	public function addI()
	{
		$this->configurator->addTag('I');
		$this->configurator->setTagTemplate('I', '<i><xsl:apply-templates /></i>');
	}

	public function addU()
	{
		$this->configurator->addTag('U');
		$this->configurator->setTagTemplate('U', '<span style="text-decoration:underline"><xsl:apply-templates /></span>');
	}

	public function addS()
	{
		$this->configurator->addTag('S');
		$this->configurator->setTagTemplate('S', '<s><xsl:apply-templates /></s>');
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
		$this->configurator->addTag('URL');
		$this->configurator->addTagRule('URL', 'denyChild', 'URL');
		$this->configurator->addTagRule('URL', 'denyDescendant', 'URL');

		$this->configurator->addAttribute('URL', 'url', '#url');
		$this->configurator->addAttribute('URL', 'title', array('required' => false));

		$this->configurator->setTagTemplate(
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
		$this->configurator->addTag('IMG', array(
			'defaultChildRule' => 'deny',
			'defaultDescendantRule' => 'deny'
		));

		$this->configurator->addAttribute('IMG', 'src', '#url');
		$this->configurator->addAttribute('IMG', 'alt', array('required' => false));
		$this->configurator->addAttribute('IMG', 'title', array('required' => false));

		$this->configurator->setTagTemplate(
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
		$this->configurator->addTag('LIST', array(
			'trimBefore'   => true,
			'trimAfter'    => true,
			'ltrimContent' => true,
			'rtrimContent' => true
		));

		$this->configurator->addAttribute('LIST', 'start', array(
			'filter'   => '#uint',
			'required' => false
		));

		$this->configurator->addAttribute('LIST', 'style', array(
			'filter'       => '#regexp',
			'defaultValue' => 'disc',
			'required'     => false,
			'regexp'       => '/^(?:[a-z\\-]+|[0-9]+)$/iD'
		));

		$this->configurator->setTagXSL(
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

		$this->configurator->addTag('LI', array(
			'trimBefore'   => true,
			'trimAfter'    => true,
			'ltrimContent' => true,
			'rtrimContent' => true
		));

		// [*] should only be used directly under [LIST]
		$this->configurator->addTagRule('LI', 'requireParent', 'LIST');

		// also, let's make so that when we have two consecutive [*] we close
		// the first one when opening the second, instead of it behind its child
		$this->configurator->addTagRule('LI', 'closeParent', 'LI');

		$this->configurator->setTagTemplate('LI', '<li><xsl:apply-templates /></li>');

		// make [LIST] and [LI] play nice with the Paragrapher plugin
		$this->configurator->addTagRule('LIST', 'denyChild', 'P');
		$this->configurator->addTagRule('LIST', 'denyChild', 'BR');
		$this->configurator->addTagRule('LI', 'denyChild', 'P');
		$this->configurator->addTagRule('LI', 'denyChild', 'BR');
	}

	public function addALIGN()
	{
		$this->configurator->addTag('ALIGN');
		$this->configurator->addAttribute('ALIGN', 'align', array(
			'filter' => '#regexp',
			'regexp' => '#^(?:left|right|center|justify)$#Di'
		));
		$this->configurator->setTagTemplate(
			'ALIGN', '<div style="text-align:{@align}"><xsl:apply-templates /></div>'
		);
	}

	public function addLEFT()
	{
		$this->configurator->addTag('LEFT');
		$this->configurator->setTagTemplate(
			'LEFT', '<div style="text-align:left"><xsl:apply-templates /></div>'
		);
	}

	public function addRIGHT()
	{
		$this->configurator->addTag('RIGHT');
		$this->configurator->setTagTemplate(
			'RIGHT', '<div style="text-align:right"><xsl:apply-templates /></div>'
		);
	}

	public function addCENTER()
	{
		$this->configurator->addTag('CENTER');
		$this->configurator->setTagTemplate(
			'CENTER', '<div style="text-align:center"><xsl:apply-templates /></div>'
		);
	}

	public function addJUSTIFY()
	{
		$this->configurator->addTag('JUSTIFY');
		$this->configurator->setTagTemplate(
			'JUSTIFY', '<div style="text-align:justify"><xsl:apply-templates /></div>'
		);
	}

	public function addSUB()
	{
		$this->configurator->addTag('SUB');
		$this->configurator->setTagTemplate('SUB', '<sub><xsl:apply-templates /></sub>');
	}

	public function addSUPER()
	{
		$this->configurator->addTag('SUPER');
		$this->configurator->setTagTemplate('SUPER', '<sup><xsl:apply-templates /></sup>');
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
		$this->configurator->addTag('TABLE', array('nestingLimit' => 2));
		$this->configurator->setTagTemplate(
			'TABLE',
			'<table>
				<xsl:apply-templates select="COL" />
				<xsl:apply-templates select="TR" />
			</table>'
		);

		$this->configurator->addTag('COL', array(
			'defaultChildRule' => 'deny',
			'defaultDescendantRule' => 'deny'
		));
		$this->configurator->addTagRule('COL', 'requireParent', 'TABLE');
		$this->configurator->addAttribute('COL', 'align', array(
			'filter'   => '#regexp',
			'required' => false,
			'regexp'   => '/^(?:left|right|center|align)$/iD'
		));
		$this->configurator->setTagTemplate(
			'COL',
			'<col>
				<xsl:if test="@align">
					<xsl:attribute name="style">text-align:<xsl:value-of select="@align" /></xsl:attribute>
				</xsl:if>
			</col>'
		);

		$this->configurator->addTag('TR');
		$this->configurator->addTagRule('TR', 'requireParent', 'TABLE');
		$this->configurator->setTagTemplate(
			'TR',
			'<tr>
				<xsl:apply-templates select="TD | TH" />
			</tr>'
		);

		$this->configurator->addTag('TH');
		$this->configurator->addTagRule('TH', 'requireParent', 'TR');
		$this->configurator->addAttribute('TH', 'colspan', array(
			'filter'   => '#uint',
			'required' => false
		));
		$this->configurator->addAttribute('TH', 'rowspan', array(
			'filter'   => '#uint',
			'required' => false
		));
		$this->configurator->setTagTemplate(
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

		$this->configurator->addTag('TD');
		$this->configurator->addTagRule('TD', 'requireParent', 'TR');
		$this->configurator->addAttribute('TD', 'colspan', array(
			'filter'   => '#uint',
			'required' => false
		));
		$this->configurator->addAttribute('TD', 'rowspan', array(
			'filter'   => '#uint',
			'required' => false
		));
		$this->configurator->setTagTemplate(
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
		$this->configurator->addTag('CODE', array(
			'defaultChildRule' => 'deny',
			'defaultDescendantRule' => 'deny'
		));

		$this->configurator->addAttribute('CODE', 'stx', array(
			'filterChain'  => array('strtolower', '#id'),
			'defaultValue' => 'plain'
		));

		$this->configurator->setTagTemplate(
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
		$this->configurator->addTag('HR', array(
			'defaultChildRule' => 'deny',
			'defaultDescendantRule' => 'deny',
			'trimBefore'  => true,
			'trimAfter'   => true
		));

		$this->configurator->setTagTemplate('HR', '<hr />');
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
		$this->configurator->addTag('QUOTE', array(
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

		$this->configurator->addAttribute('QUOTE', 'author', array('required' => false));
		$this->configurator->setTagTemplate(
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
		$this->configurator->addTag('EMAIL', array(
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

		$this->configurator->setTagTemplate('EMAIL', $tpl, Configurator::ALLOW_UNSAFE_TEMPLATES);
	}

	public function addCOLOR()
	{
		$this->configurator->addTag('COLOR');
		$this->configurator->addAttribute('COLOR', 'color', '#color');
		$this->configurator->setTagTemplate(
			'COLOR', '<span style="color:{@color}"><xsl:apply-templates /></span>'
		);
	}

	public function addINS()
	{
		$this->configurator->addTag('INS');
		$this->configurator->setTagTemplate('INS', '<ins><xsl:apply-templates /></ins>');
	}

	public function addDEL()
	{
		$this->configurator->addTag('DEL');
		$this->configurator->setTagTemplate('DEL', '<del><xsl:apply-templates /></del>');
	}

	public function addEM()
	{
		$this->configurator->addTag('EM');
		$this->configurator->setTagTemplate('EM', '<em><xsl:apply-templates /></em>');
	}

	public function addSTRONG()
	{
		$this->configurator->addTag('STRONG');
		$this->configurator->setTagTemplate('STRONG', '<strong><xsl:apply-templates /></strong>');
	}

	public function addSPAN()
	{
		$this->configurator->addTag('SPAN');

		$this->configurator->addAttribute('SPAN', 'class', array(
			'filter'   => '#regexp',
			'required' => false,
			'regexp'   => '/^[a-z_0-9 ]+$/Di'
		));

		$this->configurator->setTagTemplate(
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
		$this->configurator->addTag('NOPARSE', array(
			'defaultChildRule' => 'deny',
			'defaultDescendantRule' => 'deny'
		));
		$this->configurator->setTagTemplate(
			'NOPARSE',
			'<xsl:value-of select="text()" />'
		);
	}

	public function addCITE()
	{
		$this->configurator->addTag('CITE');
		$this->configurator->setTagTemplate('CITE', '<cite><xsl:apply-templates /></cite>');
	}

	public function addACRONYM()
	{
		$this->configurator->addTag('ACRONYM');
		$this->configurator->addAttribute('ACRONYM', 'title');
		$this->configurator->setTagTemplate('ACRONYM', '<acronym title="{@title}"><xsl:apply-templates /></acronym>');
	}

	public function addH1()
	{
		$this->configurator->addTag('H1');
		$this->configurator->setTagTemplate('H1', '<h1><xsl:apply-templates /></h1>');
	}

	public function addH2()
	{
		$this->configurator->addTag('H2');
		$this->configurator->setTagTemplate('H2', '<h2><xsl:apply-templates /></h2>');
	}

	public function addH3()
	{
		$this->configurator->addTag('H3');
		$this->configurator->setTagTemplate('H3', '<h3><xsl:apply-templates /></h3>');
	}

	public function addH4()
	{
		$this->configurator->addTag('H4');
		$this->configurator->setTagTemplate('H4', '<h4><xsl:apply-templates /></h4>');
	}

	public function addH5()
	{
		$this->configurator->addTag('H5');
		$this->configurator->setTagTemplate('H5', '<h5><xsl:apply-templates /></h5>');
	}

	public function addH6()
	{
		$this->configurator->addTag('H6');
		$this->configurator->setTagTemplate('H6', '<h6><xsl:apply-templates /></h6>');
	}

	public function addDL()
	{
		$this->configurator->addTag('DL', array(
			'trimBefore'   => true,
			'trimAfter'    => true,
			'ltrimContent' => true,
			'rtrimContent' => true
		));

		$this->configurator->setTagTemplate('DL', '<dl><xsl:apply-templates select="DT | DD" /></dl>');

		foreach (array('DT', 'DD') as $tagName)
		{
			$this->configurator->addTag($tagName, array(
				'trimBefore'   => true,
				'trimAfter'    => true,
				'ltrimContent' => true,
				'rtrimContent' => true
			));

			$this->configurator->addTagRule($tagName, 'requireParent', 'DL');
			$this->configurator->addTagRule($tagName, 'closeParent', 'DT');
			$this->configurator->addTagRule($tagName, 'closeParent', 'DD');
		}

		$this->configurator->setTagTemplate('DT', '<dt><xsl:apply-templates /></dt>');
		$this->configurator->setTagTemplate('DD', '<dd><xsl:apply-templates /></dd>');
	}

	public function addFLOAT()
	{
		$this->configurator->addTag('FLOAT', array(
			'trimAfter'  => true
		));
		$this->configurator->addAttribute('FLOAT', 'float', array(
			'filter' => '#regexp',
			'regexp' => '#^(?:left|right|none)$#Di'
		));
		$this->configurator->setTagTemplate(
			'FLOAT', '<div style="float:{@float}"><xsl:apply-templates /></div>'
		);
	}

	public function addCLEAR()
	{
		$this->configurator->addTag('CLEAR');
		$this->configurator->addAttribute('CLEAR', 'clear', array(
			'filter' => '#regexp',
			'regexp' => '#^(?:left|right|both)$#Di',
			'defaultValue' => 'both'
		));
		$this->configurator->setTagTemplate(
			'CLEAR', '<div style="clear:{@clear}"><xsl:apply-templates /></div>'
		);
	}
}