<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;
use s9e\TextFormatter\Configurator\RecursiveParser\AbstractRecursiveMatcher;
abstract class AbstractConvertor extends AbstractRecursiveMatcher
{
	protected function getAttributeName($expr)
	{
		return \preg_replace('([\\s@])', '', $expr);
	}
	protected function normalizeNumber($sign, $number)
	{
		$number = \ltrim($number, '0');
		return ($number === '') ? '0' : $sign . $number;
	}
}