<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use s9e\TextFormatter\Parser;

abstract class ParserBase
{
	/**
	* @var array
	*/
	protected $config;

	/**
	* @var Parser
	*/
	protected $parser;

	/**
	* Constructor
	*
	* @param Parser $parser
	* @param array  $config
	*/
	final public function __construct(Parser $parser, array $config)
	{
		$this->parser = $parser;
		$this->config = $config;

		$this->setUp();
	}

	/**
	* Plugin's setup
	*
	* @return void
	*/
	protected function setUp()
	{
	}

	/**
	* @param  string $text
	* @param  array  $matches If the config array has a "regexp" key, the corresponding matches are
	*                         passed as second parameter. Otherwise, an empty array is passed
	* @return void
	*/
	abstract public function parse($text, array $matches);
}