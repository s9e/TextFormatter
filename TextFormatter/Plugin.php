<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter;

abstract class Plugin
{
	/**
	* @var ConfigBuilder
	*/
	protected $cb;

	/**
	* @var Parser
	*/
	protected $parser;

	public function registerConfigBuilder(ConfigBuilder $cb)
	{
		$this->cb = $cb;
	}

	public function registerParser(Parser $parser)
	{
		$this->parser = $parser;
	}

	/**
	* @return array
	*/
	public function getConfig();

	/**
	* @return array
	*/
	public function getTags();
}