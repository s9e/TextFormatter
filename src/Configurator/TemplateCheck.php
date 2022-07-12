<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use DOMElement;
use s9e\TextFormatter\Configurator\Items\Tag;

/**
* @codeCoverageIgnore
*/
abstract class TemplateCheck
{
	/**
	* XSL namespace
	*/
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	/**
	* Check a template for infractions to this check and throw any relevant Exception
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag     $tag      Tag this template belongs to
	* @return void
	*/
	abstract public function check(DOMElement $template, Tag $tag);
}