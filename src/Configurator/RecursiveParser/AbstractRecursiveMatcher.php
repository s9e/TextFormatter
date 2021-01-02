<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RecursiveParser;

use s9e\TextFormatter\Configurator\RecursiveParser;

abstract class AbstractRecursiveMatcher implements MatcherInterface
{
	/**
	* @var RecursiveParser
	*/
	protected $parser;

	/**
	* @param  RecursiveParser $parser
	* @return void
	*/
	public function __construct(RecursiveParser $parser)
	{
		$this->parser = $parser;
	}

	/**
	* Parse given string and return its value
	*
	* @param  string $str
	* @param  string $restrict Pipe-separated list of allowed matches (ignored if empty)
	* @return mixed
	*/
	protected function recurse(string $str, string $restrict = '')
	{
		return $this->parser->parse($str, $restrict)['value'];
	}
}