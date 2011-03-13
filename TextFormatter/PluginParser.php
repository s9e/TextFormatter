<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter;

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

	public function setUp()
	{
	}

	/**
	* @return array
	*/
	public function getTags();
}