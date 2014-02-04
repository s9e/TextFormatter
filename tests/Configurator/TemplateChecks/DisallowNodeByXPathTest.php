<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMDocument;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowNodeByXPath;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowNodeByXPath
*/
class DisallowNodeByXPathTest extends Test
{
	protected function loadTemplate($template)
	{
		$xml = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $template
		     . '</xsl:template>';

		$dom = new DOMDocument;
		$dom->loadXML($xml);

		return $dom->documentElement;
	}

	/**
	* @testdox '//script[@src]' disallows <div><script src=""/></div>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Node 'script' is disallowed because it matches '//script[@src]'
	*/
	public function testDisallowed()
	{
		$node = $this->loadTemplate('<div><script src=""/></div>');

		try
		{
			$check = new DisallowNodeByXPath('//script[@src]');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox '//script[@src]' allows <div><script/></div>
	*/
	public function testAllowed()
	{
		$node = $this->loadTemplate('<div><script/></div>');
		$check = new DisallowNodeByXPath('//script[@src]');
		$check->check($node, new Tag);
	}
}