<?php

use s9e\Toolkit\Markup\ConfigBuilder,
    s9e\Toolkit\Markup\Parser,
    s9e\Toolkit\Markup\Renderer;

include_once __DIR__ . '/../ConfigBuilder.php';
include_once __DIR__ . '/../Parser.php';
include_once __DIR__ . '/../Renderer.php';

class CookbookTest extends \PHPUnit_Framework_TestCase
{
	// #url
	public function testUrl()
	{
		$cb = new ConfigBuilder;

		//======================================================================
		$cb->addBBCode('url', array(
			'default_param'    => 'url',
			'content_as_param' => true
		));

		// BBCode name, param name, param type (incidentally all identical)
		// followed by whether it's a required param
		$cb->addBBCodeParam('url', 'url', 'url', true);

		$cb->setBBCodeTemplate('url', '<a href="{@url}"><xsl:apply-templates/></a>');
		//======================================================================

		$this->assertThatItWorks($cb, array(
			'[url]http://example.com[/url]'
			=> '<a href="http://example.com">http://example.com</a>',

			'[url=http://example.com]best site ever[/url]'
			=> '<a href="http://example.com">best site ever</a>'
		));
	}

	// #list
	public function testList()
	{
		$cb = new ConfigBuilder;

		//======================================================================
		$cb->addBBCode('list', array(
			'ltrim_content' => true,
			'rtrim_content' => true
		));
		$cb->addBBCode('li', array(
			'ltrim_content' => true,
			'rtrim_content' => true
		));

		// create an alias so that [*] be interpreted as [li]
		$cb->addBBCodeAlias('li', '*');

		// [*] should only be used directly under [list]
		$cb->addBBCodeRule('li', 'require_parent', 'list');

		// also, let's make so that when we have two consecutive [*] we close
		// the first one when opening the second, instead of it behind its child
		$cb->addBBCodeRule('li', 'close_parent', 'li');

		$cb->setBBCodeTemplate('list', '<ul><xsl:apply-templates/></ul>');
		$cb->setBBCodeTemplate('li',   '<li><xsl:apply-templates/></li>');
		//======================================================================

		$this->assertThatItWorks($cb, array(
			'[list]
				[*]FIRST
				[*]SECOND
			[/list]'
			=> '<ul><li>FIRST</li><li>SECOND</li></ul>'
		));
	}

	// #quote
	public function testQuote()
	{
		$cb = new ConfigBuilder;

		//======================================================================
		$cb->addBBCode('quote', array(
			'nesting_limit' => 3,
			'default_param' => 'author',
			'trim_before'   => true,
			'trim_after'    => true,
			'ltrim_content' => true,
			'rtrim_content' => true
		));

		$cb->addBBCodeParam('quote', 'author', 'text', false);
		$cb->setBBCodeTemplate(
			'quote',
			'<div class="quote">
				<xsl:choose>
					<xsl:when test="@author">
						<div class="author"><xsl:value-of select="@author" /> wrote:</div>
					</xsl:when>
					<xsl:otherwise>
						<div class="noauthor">Generic quote</div>
					</xsl:otherwise>
				</xsl:choose>

				<xsl:apply-templates />
			</div>'
		);
		//======================================================================

		$this->assertThatItWorks($cb, array(
			"[quote]Lorem ipsum[/quote]
				...Some text...
			[quote=bob]Hello I'm Bob.[/quote]"
			=>
			'<div class="quote"><div class="noauthor">Generic quote</div>Lorem ipsum</div>...Some text...<div class="quote"><div class="author">bob wrote:</div>Hello I\'m Bob.</div>',

			'[quote][quote][quote][quote][quote]Quote pyramids are so funny![/quote][/quote][/quote][/quote][/quote]'
			=>
			'<div class="quote"><div class="noauthor">Generic quote</div><div class="quote"><div class="noauthor">Generic quote</div><div class="quote"><div class="noauthor">Generic quote</div>[quote][quote]Quote pyramids are so funny!</div></div></div>[/quote][/quote]'
		));
	}

	// #size
	public function testSize()
	{
		$cb = new ConfigBuilder;

		//======================================================================
		// Create a [size] BBCode, with its default param being "px"
		// [size=10] is the same as [size px=10]
		$cb->addBBCode('size', array('default_param' => 'px'));

		// BBCode name, param name, param type, is_required
		$cb->addBBCodeParam('size', 'px', 'font-size', true);

		// Now we need a custom filter for the "font-size" type
		function checkFontSize($v, $conf, &$msgs)
		{
			if ($v < $conf['min'])
			{
				// We can pass error messages back to the parser if we want
				$msgs['warning'][] = array(
					'msg'    => 'Font size must be at least %d',
					'params' => array($conf['min'])
				);
				return $conf['min'];
			}
			elseif ($v > $conf['max'])
			{
				$msgs['warning'][] = array(
					'msg'    => 'Font size is limited to %d',
					'params' => array($conf['max'])
				);
				return $conf['max'];
			}

			// Make sure it's a number, you don't want to add XSS to your tags!
			return (int) $v;
		};

		$cb->setFilter('font-size', 'checkFontSize', array(
			'min' => 7,
			'max' => 20
		));

		$cb->setBBCodeTemplate('size', '<span style="font-size: {@px}px"><xsl:apply-templates/></span>');
		//======================================================================

		$this->assertThatItWorks($cb, array(
			'[size=5]too small[/size]'
			=> '<span style="font-size: 7px">too small</span>',

			'[size=16]big[/size]'
			=> '<span style="font-size: 16px">big</span>',

			'[size=50]too big[/size]'
			=> '<span style="font-size: 20px">too big</span>'
		));
	}

	// #nl2br-after-rendering
	public function testNl2brAfterRendering()
	{
		$cb = new ConfigBuilder;

		//======================================================================
		$cb->addBBCodeFromExample(
			'[quote={TEXT1}]{TEXT2}[/quote]',
			'<div class="quote">
				<div class="author">{TEXT1} wrote:</div>
				<div class="content">{TEXT2}</div>
			</div>'
		);

		$text = "[quote='Uncle Joe']First line.\nSecond line.[/quote]";
		$xml  = $cb->getParser()->parse($text);
		$html = $cb->getRenderer()->render($xml);

		// Now add <br /> tags before displaying the content
		$html = nl2br($html);
		//======================================================================

		$expected = '<div class="quote"><div class="author">Uncle Joe wrote:</div><div class="content">First line.' . "<br />\nSecond line.</div></div>";

		$this->assertSame($expected, $html);
	}

	// #nl2br-before-rendering
	public function testNl2brBeforeRendering()
	{
		$cb = new ConfigBuilder;

		//======================================================================
		$cb->addBBCodeFromExample(
			'[quote={TEXT1}]{TEXT2}[/quote]',
			'<div class="quote">
				<div class="author">{TEXT1} wrote:</div>
				<div class="content">{TEXT2}</div>
			</div>'
		);

		// Add a template rule to the XSL, to preserve <br/> tags
		$cb->addXSL('<xsl:template match="br"><br/></xsl:template>');

		$text = "[quote='Uncle Joe']First line.\nSecond line.[/quote]";
		$xml  = $cb->getParser()->parse($text);

		// Add <br /> tags
		$xml  = nl2br($xml);

		$html = $cb->getRenderer()->render($xml);
		//======================================================================

		$expected = '<div class="quote"><div class="author">Uncle Joe wrote:</div><div class="content">First line.' . "<br/>\nSecond line.</div></div>";

		$this->assertSame($expected, $html);
	}

	// #revert
	public function testRevert()
	{
		$cb = new ConfigBuilder;

		//======================================================================
		$cb->addBBCodeFromExample('[b]{TEXT}[/b]', '<b>{TEXT}</b>');

		$text  = "Some [b]bold[/b] text.";
		$xml   = $cb->getParser()->parse($text);

		// Revert using plain PHP functions
		$orig1 = html_entity_decode(strip_tags($xml), ENT_QUOTES, 'utf-8');

		// Revert using DOM
		$dom   = new DOMDocument;
		$dom->loadXML($xml);
		$orig2 = $dom->textContent;
		//======================================================================

		$this->assertSame($text, $orig1);
		$this->assertSame($text, $orig2);
	}

	protected function assertThatItWorks(ConfigBuilder $cb, array $examples)
	{
		$parser   = $cb->getParser();
		$renderer = $cb->getRenderer();

		foreach ($examples as $text => $expected)
		{
			$this->assertSame(
				$expected,
				$renderer->render($parser->parse($text))
			);
		}
	}
}