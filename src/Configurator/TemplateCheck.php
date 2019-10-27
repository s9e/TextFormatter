<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;
use DOMElement;
use s9e\TextFormatter\Configurator\Items\Tag;
abstract class TemplateCheck
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';
	abstract public function check(DOMElement $template, Tag $tag);
}