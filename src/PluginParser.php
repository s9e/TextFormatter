<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

abstract class PluginParser
{
	/**
	* @var Parser
	*/
	protected $parser;

	/**
	* @var array
	*/
	protected $config;

	public function __construct(Parser $parser, array $config)
	{
		$this->parser = $parser;
		$this->config = $config;

		$this->setUp();
	}

	// @codeCoverageIgnoreStart
	public function setUp()
	{
	}
	// @codeCoverageIgnoreEnd

	/**
	* @param  string $text
	* @param  array  $matches If the config array has a "regexp" key, the corresponding matches are
	*                passed as second parameter. Otherwise, an empty array is passed
	* @return array
	*/
	abstract public function getTags($text, array $matches);
}