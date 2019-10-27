<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser\Normalizer;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser\Optimizer;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser\Parser;
class TemplateParser
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';
	public static $voidRegexp = '/^(?:area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr)$/Di';
	public static function parse($template)
	{
		$parser = new Parser(new Normalizer(new Optimizer));
		return $parser->parse($template);
	}
}