<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter;

use RuntimeException;

/**
* This class attempts to hold the definitions of the most commonly requested BBCodes.
* It is partially based on user requests found in forum software-oriented websites.
*
* @link http://www.phpbb.com/kb/article/adding-custom-bbcodes-in-phpbb3/
*/
class PredefinedBBCodes
{
	/**
	* @var ConfigBuilder
	*/
	protected $cb;

	public function __construct(ConfigBuilder $cb)
	{
		$this->cb = $cb;
	}

	public function __call($methodName, $args)
	{
		if (!preg_match('#^add([A-Z0-9]+)$#D', $methodName, $m))
		{
			// @codeCoverageIgnoreStart
			throw new RuntimeException('Call to undefined method ' . __CLASS__ . '::' . $methodName . '()');
			// @codeCoverageIgnoreEnd
		}

		$this->forwardCall($m[1], array(), $args);
	}

	protected function forwardCall($tagName, array $bbcodeConfig = array(), array $callParams = array())
	{
		if (!$this->cb->tagExists($tagName))
		{
			call_user_func_array(
				array($this->cb->predefinedTags, 'add' . $tagName),
				$callParams
			);
		}

		$this->cb->BBCodes->addBBCodeAlias($tagName, $tagName, $bbcodeConfig);
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
		$this->forwardCall('URL', array(
			'defaultAttr' => 'url',
			'contentAttr' => 'url'
		));
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
		$this->forwardCall('IMG', array(
			'defaultAttr' => 'src',
			'contentAttr' => 'src',
			'autoClose'   => true
		));
	}

	public function addLIST()
	{
		$this->forwardCall('LIST', array(
			'defaultAttr' => 'style'
		));

		// [*] maps to <LI>
		$this->cb->BBCodes->addBBCodeAlias('*', 'LI');
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
		$this->forwardCall('ALIGN', array(
			'defaultAttr' => 'align'
		));
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

	/**
	* Basic [TABLE], [COL], [TR], [TH] and [TD] tags.
	* [TD] accepts two optional arguments: colspan and rowspan.
	*
	* Misplaced text, e.g. [TR]xxx[TD][/TD][/TR], is parsed normally but doesn't appear in the
	* HTML ouput.
	*/
	public function addTABLE()
	{
		$this->forwardCall('TABLE');
		$this->cb->BBCodes->addBBCodeAlias('COL', 'COL', array('autoClose' => true));
		$this->cb->BBCodes->addBBCodeAlias('TR', 'TR');
		$this->cb->BBCodes->addBBCodeAlias('TH', 'TH');
		$this->cb->BBCodes->addBBCodeAlias('TD', 'TD');
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
		$this->forwardCall('CODE', array(
			'defaultAttr' => 'stx'
		));
	}

	public function addHR()
	{
		$this->forwardCall('HR', array(
			'autoClose' => true
		));
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
		$this->forwardCall(
			'QUOTE',
			array('defaultAttr' => 'author'),
			array($nestingLevel, $authorStr)
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
		$this->forwardCall('EMAIL', array(
			'defaultAttr' => 'email',
			'contentAttr' => 'content'
		));
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
		$this->forwardCall('COLOR', array(
			'defaultAttr' => 'color'
		));
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

	public function addACRONYM()
	{
		$this->forwardCall('ACRONYM', array(
			'defaultAttr' => 'title'
		));
	}

	public function addDL()
	{
		$this->forwardCall('DL');
		$this->cb->BBCodes->addBBCodeAlias('DT', 'DT');
		$this->cb->BBCodes->addBBCodeAlias('DD', 'DD');
	}
}