<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use DOMElement;
use s9e\TextFormatter\Configurator\Items\Tag;

abstract class TemplateCheck
{
	abstract public function check(DOMElement $template, Tag $tag);
}