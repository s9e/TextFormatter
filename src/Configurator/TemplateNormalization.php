<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use DOMElement;

abstract class TemplateNormalization
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	public $onlyOnce = \false;

	abstract public function normalize(DOMElement $template);

	public static function lowercase($str)
	{
		return \strtr($str, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
	}
}