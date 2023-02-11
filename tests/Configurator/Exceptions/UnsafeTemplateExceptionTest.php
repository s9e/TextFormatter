<?php

namespace s9e\TextFormatter\Tests\Configurator;

use DOMDocument;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
*/
class UnsafeTemplateExceptionTest extends Test
{
	/**
	* @testdox getNode() returns the stored node
	*/
	public function testGetNode()
	{
		$dom = new DOMDocument;
		$dom->loadXML('<foo/>');
		$exception = new UnsafeTemplateException('Msg', $dom->documentElement);

		$this->assertSame($dom->documentElement, $exception->getNode());
	}

	/**
	* @testdox setNode() sets the stored node
	*/
	public function testSetNode()
	{
		$dom = new DOMDocument;
		$dom->loadXML('<x><y/></x>');

		$exception = new UnsafeTemplateException('Msg', $dom->documentElement);
		$exception->setNode($exception->getNode()->firstChild);

		$this->assertSame($dom->documentElement->firstChild, $exception->getNode());
	}

	/**
	* @testdox highlightNode() returns the template's source formatted and with the stored node highlighted
	* @dataProvider getHighlights
	*/
	public function testHighlightNode($template, $expected)
	{
		$configurator = new Configurator;
		$tag = new Tag;
		$tag->template = $template;

		try
		{
			$configurator->templateChecker->checkTag($tag);
			$this->markTestSkipped('Template checker did not generate an exception');

			return;
		}
		catch (UnsafeTemplateException $e)
		{
		}

		$this->assertSame($expected, $e->highlightNode());
	}

	public static function getHighlights()
	{
		return [
			[
				'<script><xsl:apply-templates/></script>',
'&lt;script&gt;
  <span style="background-color:#ff0">&lt;xsl:apply-templates/&gt;</span>
&lt;/script&gt;'
			],
			[
				'<a href="{@foo}"><xsl:apply-templates/></a>',
'&lt;a <span style="background-color:#ff0">href=&quot;{@foo}&quot;</span>&gt;
  &lt;xsl:apply-templates/&gt;
&lt;/a&gt;'
			],
			[
				'<?php foo(); ?>',
				'<span style="background-color:#ff0">&lt;?php foo(); ?&gt;</span>'
			]
		];
	}
}