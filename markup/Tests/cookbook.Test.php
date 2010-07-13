<?php

use s9e\toolkit\markup\config_builder,
    s9e\toolkit\markup\parser,
    s9e\toolkit\markup\renderer;

include_once __DIR__ . '/../config_builder.php';
include_once __DIR__ . '/../parser.php';
include_once __DIR__ . '/../renderer.php';

class testCookbook extends \PHPUnit_Framework_TestCase
{
	// #url
	public function testUrl()
	{
		$cb = new config_builder;

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

	// #url
	public function testList()
	{
		$cb = new config_builder;

		//======================================================================
		$cb->addBBCode('list');
		$cb->addBBCode('li');

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
			'[list][*]FIRST[*]SECOND[/list]'
			=> '<ul><li>FIRST</li><li>SECOND</li></ul>'
		));
	}

	// #size
	public function testSize()
	{
		$cb = new config_builder;

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

	protected function assertThatItWorks(config_builder $cb, array $examples)
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