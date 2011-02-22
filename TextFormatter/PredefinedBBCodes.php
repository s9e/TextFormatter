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
		$this->cb->addBBCodeFromExample('[B]{TEXT}[/B]', '<strong>{TEXT}</strong>');
	}

	public function addI()
	{
		$this->cb->addBBCodeFromExample('[I]{TEXT}[/I]', '<em>{TEXT}</em>');
	}

	public function addU()
	{
		$this->cb->addBBCodeFromExample(
			'[U]{TEXT}[/U]',
			'<span style="text-decoration: underline">{TEXT}</span>'
		);
	}

	public function addS()
	{
		$this->cb->addBBCodeFromExample(
			'[S]{TEXT}[/S]',
			'<span style="text-decoration: line-through">{TEXT}</span>'
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
		$this->cb->addBBCode('URL', array(
			'default_param'    => 'url',
			'content_as_param' => true
		));

		$this->cb->addBBCodeParam('URL', 'url', 'url');
		$this->cb->addBBCodeParam('URL', 'title', 'text', array('is_required' => false));

		$this->cb->setBBCodeTemplate(
			'URL',
			'<a href="{@url}"><xsl:if test="@title"><xsl:attribute name="title"><xsl:value-of select="@title"/></xsl:attribute></xsl:if><xsl:apply-templates/></a>'
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
		$this->cb->addBBCode('IMG', array(
			'default_param'    => 'src',
			'content_as_param' => true,
			'default_rule'     => 'deny'
		));

		$this->cb->addBBCodeParam('IMG', 'src', 'url');
		$this->cb->addBBCodeParam('IMG', 'alt', 'text', array('is_required' => false));
		$this->cb->addBBCodeParam('IMG', 'title', 'text', array('is_required' => false));

		$this->cb->setBBCodeTemplate(
			'IMG',
			'<img src="{@src}"><xsl:if test="@alt"><xsl:attribute name="alt"><xsl:value-of select="@alt"/></xsl:attribute></xsl:if><xsl:if test="@title"><xsl:attribute name="title"><xsl:value-of select="@title"/></xsl:attribute></xsl:if></img>'
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
		$this->cb->addBBCode('LIST', array(
			'default_param' => 'style',
			'trim_before'   => true,
			'trim_after'    => true,
			'ltrim_content' => true,
			'rtrim_content' => true
		));

		$this->cb->addBBCodeParam('LIST', 'start', 'uint', array('is_required' => false));

		$this->cb->addBBCodeParam('LIST', 'style', 'regexp', array(
			'is_required' => false,
			'regexp' => '/^' . ConfigBuilder::buildRegexpFromList($styles) . '$/iD'
		));

		$this->cb->setBBCodeTemplate(
			'LIST',
			'<ol><xsl:attribute name="style">list-style-type:<xsl:choose><xsl:when test="not(@style)">disc</xsl:when><xsl:when test="@style=\'1\'">decimal</xsl:when><xsl:when test="@style=\'01\'">decimal-leading-zero</xsl:when><xsl:when test="@style=\'a\'">lower-alpha</xsl:when><xsl:when test="@style=\'A\'">upper-alpha</xsl:when><xsl:when test="@style=\'i\'">lower-roman</xsl:when><xsl:when test="@style=\'I\'">upper-roman</xsl:when><xsl:otherwise><xsl:value-of select="@style"/></xsl:otherwise></xsl:choose></xsl:attribute><xsl:if test="@start"><xsl:attribute name="start"><xsl:value-of select="@start"/></xsl:attribute></xsl:if><xsl:apply-templates/></ol>'
		);

		// [LI]
		$this->cb->addBBCode('LI', array(
			'trim_before'   => true,
			'trim_after'    => true,
			'ltrim_content' => true,
			'rtrim_content' => true
		));

		// create an alias so that [*] be interpreted as [LI]
		$this->cb->addBBCodeAlias('LI', '*');

		// [*] should only be used directly under [LIST]
		$this->cb->addBBCodeRule('LI', 'require_parent', 'list');

		// also, let's make so that when we have two consecutive [*] we close
		// the first one when opening the second, instead of it behind its child
		$this->cb->addBBCodeRule('LI', 'close_parent', 'LI');

		$this->cb->setBBCodeTemplate('LI', '<li><xsl:apply-templates/></li>');
	}

	public function addGOOGLEVIDEO()
	{
		$this->cb->addBBCodeFromExample(
			'[googlevideo]{INT}[/googlevideo]',
			'<object type="application/x-shockwave-flash" data="http://video.google.com/googleplayer.swf?docId={INT}" width="400" height="326"><param name="movie" value="http://video.google.com/googleplayer.swf?docId={INT}"/><param name="allowScriptAcess" value="sameDomain"/><param name="quality" value="best"/><param name="scale" value="noScale"/><param name="salign" value="TL"/><param name="FlashVars" value="playerMode=embedded"/></object>'
		);
	}

	public function addYOUTUBE()
	{
		$this->cb->addBBCodeFromExample(
			'[youtube]{IDENTIFIER}[/youtube]',
			'<object type="application/x-shockwave-flash" data="http://www.youtube.com/v/{IDENTIFIER}" width="425" height="350"><param name="movie" value="http://www.youtube.com/v/{IDENTIFIER}" /><param name="wmode" value="transparent" /></object>'
		);
	}

	public function addALIGN()
	{
		$this->cb->addBBCodeFromExample(
			'[align={CHOICE:left,right,center,justify}]{TEXT}[/align]',
			'<div style="text-align:{CHOICE}">{TEXT}</div>'
		);
	}

	public function addLEFT()
	{
		$this->cb->addBBCodeFromExample(
			'[left]{TEXT}[/left]',
			'<div style="text-align:left">{TEXT}</div>'
		);
	}

	public function addRIGHT()
	{
		$this->cb->addBBCodeFromExample(
			'[right]{TEXT}[/right]',
			'<div style="text-align:right">{TEXT}</div>'
		);
	}

	public function addCENTER()
	{
		$this->cb->addBBCodeFromExample(
			'[center]{TEXT}[/center]',
			'<div style="text-align:center">{TEXT}</div>'
		);
	}

	public function addJUSTIFY()
	{
		$this->cb->addBBCodeFromExample(
			'[justify]{TEXT}[/justify]',
			'<div style="text-align:justify">{TEXT}</div>'
		);
	}

/**
	public function addQUOTE($nestingLevel = 3)
	{
	}
/**/
}