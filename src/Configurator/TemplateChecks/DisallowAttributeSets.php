<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

class DisallowAttributeSets extends TemplateCheck
{
	/**
	* Test whether the template contains an <xsl:attribute-set/>
	*
	* Templates are checked outside of their stylesheet, which means we don't have access to the
	* <xsl:attribute-set/> declarations and we can't easily test them. Attribute sets are fairly
	* uncommon and there's little incentive to use them in small stylesheets
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$nodes = $xpath->query('//@use-attribute-sets');

		if ($nodes->length)
		{
			throw new UnsafeTemplateException('Cannot assess the safety of attribute sets', $nodes->item(0));
		}
	}
}