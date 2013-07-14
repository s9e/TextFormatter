<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use DOMNode;

/**
* @codeCoverageIgnore
*/
abstract class TemplateNormalization
{
	/**
	* XSL namespace
	*/
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	/**
	* @var bool Whether this normalization should be applied only once per template
	*/
	public $onlyOnce = false;

	/**
	* Apply this normalization rule to given template
	*
	* @param  DOMNode $template <xsl:template/> node
	* @return void
	*/
	abstract public function normalize(DOMNode $template);
}