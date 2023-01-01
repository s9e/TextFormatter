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

class DisallowUnsafeCopyOf extends TemplateCheck
{
	/**
	* Check for unsafe <xsl:copy-of/> elements
	*
	* Any select expression that is not a set of named attributes is considered unsafe
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		$nodes = $template->getElementsByTagNameNS(self::XMLNS_XSL, 'copy-of');
		foreach ($nodes as $node)
		{
			$expr = $node->getAttribute('select');

			if (!preg_match('#^@[-\\w]*(?:\\s*\\|\\s*@[-\\w]*)*$#D', $expr))
			{
				throw new UnsafeTemplateException("Cannot assess the safety of '" . $node->nodeName . "' select expression '" . $expr . "'", $node);
			}
		}
	}
}