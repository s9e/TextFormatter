<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

class DisallowUnsafeCopyOf extends TemplateCheck
{
	/*
	* Check for unsafe <xsl:copy-of/> elements
	*
	* Any select expression that is not a single attribute is considered unsafe
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$nodes = $template->getElementsByTagNameNS('http://www.w3.org/1999/XSL/Transform', 'copy-of');

		foreach ($nodes as $node)
		{
			$expr = $node->getAttribute('select');

			if (!\preg_match('#^@[-\\w]*$#D', $expr))
				throw new UnsafeTemplateException("Cannot assess the safety of '" . $node->nodeName . "' select expression '" . $expr . "'", $node);
		}
	}
}