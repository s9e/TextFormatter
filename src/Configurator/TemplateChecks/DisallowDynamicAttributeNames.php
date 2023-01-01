<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

class DisallowDynamicAttributeNames extends TemplateCheck
{
	/**
	* Test for the presence of an <xsl:attribute/> node using a dynamic name
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$nodes = $template->getElementsByTagNameNS(self::XMLNS_XSL, 'attribute');
		foreach ($nodes as $node)
		{
			if (strpos($node->getAttribute('name'), '{') !== false)
			{
				throw new UnsafeTemplateException('Dynamic <xsl:attribute/> names are disallowed', $node);
			}
		}
	}
}