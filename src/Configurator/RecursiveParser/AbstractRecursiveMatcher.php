<?php declare(strict_types=1);
/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RecursiveParser;
use s9e\TextFormatter\Configurator\RecursiveParser;
abstract class AbstractRecursiveMatcher implements MatcherInterface
{
	protected $parser;
	public function __construct(RecursiveParser $parser)
	{
		$this->parser = $parser;
	}
	protected function recurse(string $str, string $restrict = '')
	{
		return $this->parser->parse($str, $restrict)array('value');
	}
}