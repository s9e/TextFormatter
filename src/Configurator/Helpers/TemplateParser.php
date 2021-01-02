<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use s9e\TextFormatter\Configurator\Helpers\TemplateParser\Normalizer;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser\Optimizer;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser\Parser;

class TemplateParser
{
	/**
	* XSL namespace
	*/
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	/**
	* @var string Regexp that matches the names of all void elements
	* @link http://www.w3.org/TR/html-markup/syntax.html#void-elements
	*/
	public static $voidRegexp = '/^(?:area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr)$/Di';

	/**
	* Parse a template into an internal representation
	*
	* @param  string      $template Source template
	* @return DOMDocument           Internal representation
	*/
	public static function parse($template)
	{
		$parser = new Parser(new Normalizer(new Optimizer));

		return $parser->parse($template);
	}
}