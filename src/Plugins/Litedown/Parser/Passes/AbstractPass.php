<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser\Passes;

use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Plugins\Litedown\Parser\ParsedText;

abstract class AbstractPass
{
	/**
	* @var Parser
	*/
	protected $parser;

	/**
	* @var ParsedText Text being parsed
	*/
	protected $text;

	/**
	* @param Parser     $parser
	* @param ParsedText $text
	*/
	public function __construct(Parser $parser, ParsedText $text)
	{
		$this->parser = $parser;
		$this->text   = $text;
	}

	/**
	* Parse the prepared text from stored parser
	*
	* @return void
	*/
	abstract public function parse();
}